<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security;

use Biurad\Http\Request;
use Biurad\Security\Event\AuthenticationFailureEvent;
use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUserChecker;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authenticate a user with a set of authenticators.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Authenticator implements AuthorizationCheckerInterface
{
    private TokenStorageInterface $tokenStorage;
    private AccessDecisionManagerInterface $accessDecisionManager;
    private UserCheckerInterface $userChecker;
    private ?PropertyAccessorInterface $propertyAccessor;
    private ?RequestRateLimiterInterface $limiter;
    private ?EventDispatcherInterface $eventDispatcher;
    private bool $hideUserNotFoundExceptions;

    /** @var array<int,Interfaces\AuthenticatorInterface> */
    private array $authenticators;

    /**
     * @param array<int,Interfaces\AuthenticatorInterface> $authenticators
     */
    public function __construct(
        array $authenticators,
        TokenStorageInterface $tokenStorage,
        AccessDecisionManagerInterface $accessDecisionManager,
        UserCheckerInterface $userChecker = null,
        RequestRateLimiterInterface $limiter = null,
        EventDispatcherInterface $eventDispatcher = null,
        PropertyAccessorInterface $propertyAccessor = null,
        bool $hideUserNotFoundExceptions = true
    ) {
        $this->authenticators = $authenticators;
        $this->tokenStorage = $tokenStorage;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->userChecker = $userChecker ?? new InMemoryUserChecker();
        $this->limiter = $limiter;
        $this->eventDispatcher = $eventDispatcher;
        $this->propertyAccessor = $propertyAccessor;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
    }

    public function add(AuthenticatorInterface $authenticator): void
    {
        $this->authenticators[] = $authenticator;
    }

    /**
     * Returns the user(s) representation.
     *
     * @return UserInterface|array<int,UserInterface>|null
     */
    public function getUser(bool $current = true)
    {
        $token = $this->getToken();

        if ($current || !\is_array($token)) {
            return null !== $token ? $token->getUser() : $token;
        }
        $users = [];

        foreach ($token as $tk) {
            $users[] = $tk->getUser();
        }

        return $users;
    }

    /**
     * Returns the current security token(s).
     *
     * @return TokenInterface|array<int,TokenInterface>|null
     */
    public function getToken(bool $current = true)
    {
        $token = $this->tokenStorage->getToken();

        if ($current || !$token instanceof SwitchUserToken) {
            return $token;
        }
        $users = [];

        do {
            $users[] = $token; // Include the token, it contain's a user.
            $users[] = $token = $token->getOriginalToken();
        } while ($token instanceof SwitchUserToken);

        return $users;
    }

    /**
     * Convenience method to programmatically authenticate a user and return
     * true if any success or a Response on failure.
     *
     * @param array<int,string> $credentials
     *
     * @return ResponseInterface|bool
     */
    public function authenticate(ServerRequestInterface $request, array $credentials)
    {
        if ([] !== $credentials) {
            if (\str_contains($request->getHeaderLine('CONTENT_TYPE'), 'json')) {
                $data = \json_decode((string) $request->getBody());

                if (!$data instanceof \stdClass) {
                    throw new BadRequestException('Invalid JSON.');
                }
            }

            foreach ($credentials as $offset => $key) {
                unset($credentials[$offset]);
                $credentials[$key] = Helper::getParameterValue($data ?? $request, $key, $this->propertyAccessor);
            }
        }

        if ($throttling = null !== $this->limiter && $request instanceof Request) {
            $limit = $this->limiter->consume($request->getRequest());

            if (!$limit->isAccepted()) {
                throw new TooManyLoginAttemptsAuthenticationException(\ceil(($limit->getRetryAfter()->getTimestamp() - \time()) / 60));
            }
        }

        foreach ($this->authenticators as $authenticator) {
            if (!$authenticator->supports($request)) {
                continue;
            }

            try {
                if (null === $token = $authenticator->authenticate($request, $credentials)) {
                    continue; // Allow an authenticator without a token.
                }

                if (!$token instanceof PreAuthenticatedToken) {
                    $this->userChecker->checkPreAuth($token->getUser());
                }

                if (null !== $this->eventDispatcher) {
                    $this->eventDispatcher->dispatch($event = new AuthenticationSuccessEvent($token));
                    $token = $event->getAuthenticationToken();
                }

                if ($throttling) {
                    $this->limiter->reset($request->getRequest());
                }
                $this->tokenStorage->setToken($token);
                $this->userChecker->checkPostAuth($token->getUser());

                return true;
            } catch (AuthenticationException $e) {
                // Avoid leaking error details in case of invalid user (e.g. user not found or invalid account status)
                // to prevent user enumeration via response content comparison
                if ($this->hideUserNotFoundExceptions && ($e instanceof UserNotFoundException || ($e instanceof AccountStatusException && !$e instanceof CustomUserMessageAccountStatusException))) {
                    $e = new BadCredentialsException('Bad credentials.', 0, $e);
                }

                $response = $authenticator->failure($request, $e);

                if (null !== $this->eventDispatcher) {
                    $this->eventDispatcher->dispatch($event = new AuthenticationFailureEvent($e, $authenticator, $request, $response));
                    $response = $event->getResponse();
                }

                return $response ?? false;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Checks if the attributes are granted against the current authentication token and optionally supplied subject.
     *
     * @throws AuthenticationCredentialsNotFoundException when the token storage has no authentication token and $exceptionOnNoToken is set to true
     */
    public function isGranted($attribute, $subject = null): bool
    {
        $token = $this->tokenStorage->getToken();

        if (!$token || !$token->getUser()) {
            $token = new NullToken();
        }

        return $this->accessDecisionManager->decide($token, [$attribute], $subject);
    }
}
