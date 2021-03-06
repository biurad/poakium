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

use Biurad\Security\Event\AuthenticationFailureEvent;
use Biurad\Security\Interfaces\AuthenticatorInterface;
use Biurad\Security\Interfaces\FailureHandlerInterface;
use Biurad\Security\Interfaces\RequireTokenInterface;
use Biurad\Security\RateLimiter\AbstractRequestRateLimiter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;
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
    private ?AbstractRequestRateLimiter $limiter;
    private ?EventDispatcherInterface $eventDispatcher;
    private bool $hideUserNotFoundExceptions, $eraseCredentials;
    private string $firewallName;

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
        AbstractRequestRateLimiter $limiter = null,
        EventDispatcherInterface $eventDispatcher = null,
        PropertyAccessorInterface $propertyAccessor = null,
        bool $hideUserNotFoundExceptions = true,
        bool $eraseCredentials = true,
        string $firewallName = 'main'
    ) {
        $this->firewallName = $firewallName;
        $this->authenticators = $authenticators;
        $this->tokenStorage = $tokenStorage;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->userChecker = $userChecker ?? new InMemoryUserChecker();
        $this->limiter = $limiter;
        $this->eventDispatcher = $eventDispatcher;
        $this->propertyAccessor = $propertyAccessor;
        $this->eraseCredentials = $eraseCredentials;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
    }

    public function add(AuthenticatorInterface $authenticator): void
    {
        $this->authenticators[\get_class($authenticator)] = $authenticator;
    }

    public function has(string $authenticatorClass): bool
    {
        return isset($this->authenticators[$authenticatorClass]);
    }

    public function remove(string $authenticatorClass): void
    {
        unset($this->authenticators[$authenticatorClass]);
    }

    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    /**
     * Returns the user(s) representation.
     *
     * @return UserInterface|array<int,UserInterface>|null
     */
    public function getUser(bool $current = true)
    {
        $token = $this->getToken($current);

        if (!\is_array($token)) {
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

        if ($current) {
            return $token;
        }
        $tokens = [];
        $tokenExist = -1;

        do {
            if (-1 === $tokenExist || $token !== $tokens[$tokenExist]) {
                $tokens[++$tokenExist] = $token;
            }

            if ($token instanceof SwitchUserToken) {
                $tokens[++$tokenExist] = $token = $token->getOriginalToken();
            }
        } while ($token instanceof SwitchUserToken);

        return \array_filter($tokens);
    }

    /**
     * Convenience method to programmatically authenticate a user and return
     * true if any success, else an exception or response on failure.
     *
     * @param array<int,string> $credentials The credentials to use
     * @param array<int,string> $onlyCheck   The class names list of authenticators to check
     *
     * @throws AuthenticationException if the authentication fails or credentials are invalid
     * @throws TooManyLoginAttemptsAuthenticationException if the authentication fails because of too many attempts
     *
     * @return ResponseInterface|true The response of the authentication
     */
    public function authenticate(ServerRequestInterface $request, array $credentials, array $onlyCheck = [])
    {
        if (empty($authenticators = $this->authenticators)) {
            throw new ProviderNotFoundException('No authenticator found.');
        }

        $previousToken = $this->tokenStorage->getToken();
        $credentials = Helper::getParameterValues($request, $credentials, $this->propertyAccessor);

        if (null !== $this->limiter) {
            $limit = $this->limiter->consume($request);

            if (!$limit->isAccepted()) {
                throw new TooManyLoginAttemptsAuthenticationException((int) \ceil(($limit->getRetryAfter()->getTimestamp() - \time()) / 60));
            }
        }

        foreach ($authenticators as $offset => $authenticator) {
            if (!empty($onlyCheck) && !\in_array($offset, $onlyCheck, true)) {
                continue;
            }

            if ($authenticator instanceof RequireTokenInterface) {
                $authenticator->setToken($previousToken);
            }

            if (!$authenticator->supports($request)) {
                continue;
            }

            try {
                $token = $this->executeAuthenticator($authenticator, $request, $credentials);

                if (null !== $token) {
                    $this->tokenStorage->setToken($token);
                }
            } catch (AuthenticationException $e) {
                // Avoid leaking error details in case of invalid user (e.g. user not found or invalid account status)
                // to prevent user enumeration via response content comparison
                if ($this->hideUserNotFoundExceptions && ($e instanceof UserNotFoundException || ($e instanceof AccountStatusException && !$e instanceof CustomUserMessageAccountStatusException))) {
                    $e = new BadCredentialsException('Bad credentials.', 0, $e);
                }

                $response = $authenticator instanceof FailureHandlerInterface ? $authenticator->failure($request, $e) : null;

                if (null !== $this->eventDispatcher) {
                    $this->eventDispatcher->dispatch($event = new AuthenticationFailureEvent($e, $authenticator, $request, $response));
                    $response = $event->getResponse();
                    $e = $event->getException();
                }

                if (!$response instanceof ResponseInterface) {
                    throw $e;
                }

                return $response;
            }
        }

        return true;
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

    protected function executeAuthenticator(AuthenticatorInterface $authenticator, ServerRequestInterface $request, array $credentials): ?TokenInterface
    {
        $token = $authenticator->authenticate($request, $credentials, $this->firewallName);

        if (null !== $token) {
            if (null !== $this->limiter) {
                $this->limiter->reset($request);
            }

            if (!$token instanceof PreAuthenticatedToken) {
                $this->userChecker->checkPreAuth($token->getUser());
            }

            if (null !== $this->eventDispatcher) {
                $this->eventDispatcher->dispatch($event = new AuthenticationSuccessEvent($token));
                $token = $event->getAuthenticationToken();
            }

            $this->userChecker->checkPostAuth($token->getUser());

            if ($this->eraseCredentials) {
                $token->eraseCredentials();
            }
        }

        return $token;
    }
}
