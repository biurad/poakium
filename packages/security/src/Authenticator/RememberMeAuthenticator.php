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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * The RememberMe *Authenticator* performs remember me authentication.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RememberMeAuthenticator implements AuthenticatorInterface
{
    private RememberMeHandler $rememberMeHandler;
    private TokenStorageInterface $tokenStorage;
    private ?LoggerInterface $logger;

    public function __construct(RememberMeHandler $rememberMeHandler, TokenStorageInterface $tokenStorage, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->rememberMeHandler = $rememberMeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServerRequestInterface $request): bool
    {
        // do not overwrite already stored tokens (i.e. from the session)
        if (null !== $this->tokenStorage->getToken()) {
            return false;
        }

        return \array_key_exists($this->rememberMeHandler->getCookieName(), $request->getCookieParams());
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials): ?TokenInterface
    {
        if (
            null === ($rememberMe = $credentials[$this->rememberMeHandler->getParameterName()] ?? null) ||
            !($rememberMe = 'true' === $rememberMe || 'on' === $rememberMe || '1' === $rememberMe || 'yes' === $rememberMe || true === $rememberMe)
        ) {
            return null;
        }

        if (!\str_contains($rawCookie = $request->getCookieParams()[$this->rememberMeHandler->getCookieName()], ':')) {
            throw new AuthenticationException('The cookie is incorrectly formatted.');
        }

        $user = $this->rememberMeHandler->consumeRememberMeCookie($rawCookie, $this->provider);

        return new RememberMeToken($user, 'main', $this->rememberMeHandler->getSecret());
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
}
