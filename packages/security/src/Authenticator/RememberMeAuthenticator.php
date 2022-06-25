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

namespace Biurad\Security\Authenticator;

use Biurad\Security\Handler\RememberMeHandler;
use Biurad\Security\Interfaces\AuthenticatorInterface;
use Biurad\Security\Interfaces\FailureHandlerInterface;
use Biurad\Security\Interfaces\RequireTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * The RememberMe *Authenticator* performs remember me authentication.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RememberMeAuthenticator implements AuthenticatorInterface, RequireTokenInterface, FailureHandlerInterface
{
    private RememberMeHandler $rememberMeHandler;
    private UserProviderInterface $userProvider;
    private ?TokenInterface $token = null;
    private ?LoggerInterface $logger;
    private bool $allowMultipleRememberMeTokens;

    public function __construct(
        RememberMeHandler $rememberMeHandler,
        UserProviderInterface $userProvider,
        bool $allowMultipleRememberMeTokens = false,
        LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
        $this->userProvider = $userProvider;
        $this->rememberMeHandler = $rememberMeHandler;
        $this->allowMultipleRememberMeTokens = $allowMultipleRememberMeTokens;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return null === $this->token && 'GET' === $request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials, string $firewallName): ?TokenInterface
    {
        $loadedUsers = $cookies = [];

        foreach ($request->getCookieParams() as $cookieName => $rawCookie) {
            if (empty($rawCookie) || !\str_starts_with($cookieName, $this->rememberMeHandler->getCookieName())) {
                continue;
            }

            [$loadedUser, $cookie] = $this->rememberMeHandler->consumeRememberMeCookie(\urldecode($rawCookie), $this->userProvider);
            $loadedUsers[] = $loadedUser;

            if (null !== $cookie) {
                $cookies[] = $cookie->withSecure('https' === $request->getUri()->getScheme());
            }
        }

        if (!empty($loadedUsers)) {
            $firstUser = \array_shift($loadedUsers);
            $token = new RememberMeToken($firstUser, $firewallName, $this->rememberMeHandler->getSecret());

            if (\count($loadedUsers) > 0) {
                if (!$this->allowMultipleRememberMeTokens) {
                    throw new CookieTheftException('Multiple remember me tokens were received, but multiple tokens are not allowed.');
                }

                foreach ($loadedUsers as $user) {
                    $token = new SwitchUserToken($user, $firewallName, $user->getRoles(), $token);
                }
            }

            if (\count($cookies) > 0) {
                $token->setAttribute(RememberMeHandler::REMEMBER_ME, $cookies);
            }

            return $token;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (null !== $this->logger) {
            if ($exception instanceof UserNotFoundException) {
                $this->logger->info('User for remember-me cookie not found.', ['exception' => $exception]);
            } elseif ($exception instanceof UnsupportedUserException) {
                $this->logger->warning('User class for remember-me cookie not supported.', ['exception' => $exception]);
            } elseif (!$exception instanceof CookieTheftException) {
                $this->logger->debug('Remember me authentication failed.', ['exception' => $exception]);
            }
        }

        return null;
    }

    /**
     * List of cookie which should be cleared if a user is logged out.
     *
     * @return array<int,Cookie>
     */
    public function clearCookies(ServerRequestInterface $request): array
    {
        return $this->rememberMeHandler->clearRememberMeCookies($request);
    }

    public function getRememberMeHandler(): RememberMeHandler
    {
        return $this->rememberMeHandler;
    }
}
