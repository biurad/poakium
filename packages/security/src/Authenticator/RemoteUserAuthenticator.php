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

use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This authenticator authenticates a remote user. (example by the
 * webserver) X.509 certificates.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RemoteUserAuthenticator implements AuthenticatorInterface
{
    private string $userKey;
    private string $credentialsKey;
    private UserProviderInterface $userProvider;
    private TokenStorageInterface $tokenStorage;
    private ?LoggerInterface $logger;

    public function __construct(
        UserProviderInterface $userProvider,
        TokenStorageInterface $tokenStorage,
        string $userKey = 'SSL_CLIENT_S_DN_Email',
        string $credentialsKey = 'SSL_CLIENT_S_DN',
        LoggerInterface $logger = null
    ) {
        $this->userKey = $userKey;
        $this->credentialsKey = $credentialsKey;
        $this->userProvider = $userProvider;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(?TokenInterface $token): void
    {
        if (null !== $token) {
            $this->tokenStorage->setToken($token);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServerRequestInterface $request): bool
    {
        $token = $this->tokenStorage->getToken();

        if (null !== $token && !$token instanceof PreAuthenticatedToken) {
            return $request->hasHeader('AUTH-SWITCH-USER');
        }

        if (!$username = ($request->getServerParams()[$this->userKey] ?? null)) {
            $username = $request->getServerParams()[$this->credentialsKey] ?? null;

            if (null !== $username && \preg_match('#emailAddress=([^,/@]++@[^,/]++)#', $username, $matches)) {
                $username = $matches[1];
            }
        }

        if (null === $username) {
            $this->clearToken($token);

            if (null !== $this->logger) {
                $this->logger->debug('Skipping pre-authenticated authenticator no username could be extracted.', ['authenticator' => static::class]);
            }

            return false;
        }

        // do not overwrite already stored tokens from the same user (i.e. from the session)
        if ($token instanceof PreAuthenticatedToken && $token->getUserIdentifier() === $username) {
            if (null !== $this->logger) {
                $this->logger->debug('Skipping pre-authenticated authenticator as the user already has an existing session.', ['authenticator' => static::class]);
            }

            return false;
        }

        $this->userKey = $username;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials): ?TokenInterface
    {
        if (\count($credentials) > 0) {
            throw new AuthenticationException('User credentials are already fetched remotely.');
        }
        $user = $this->userProvider->loadUserByIdentifier($this->userKey);

        return new PreAuthenticatedToken($user, 'main', $user->getRoles());
    }

    /**
     * {@inheritdoc}
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        $this->clearToken($this->tokenStorage->getToken(), $exception);

        return null;
    }

    private function clearToken(?TokenInterface $token, AuthenticationException $exception = null): void
    {
        if ($token instanceof PreAuthenticatedToken) {
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                if (null === $exception) {
                    $this->logger->info(\sprintf('Cleared pre-authenticated token, due missing user in credentials: %s, %s', $this->userKey, $this->credentialsKey));
                } else {
                    $this->logger->info('Cleared pre-authenticated token due to an exception.', ['exception' => $exception]);
                }
            }
        }
    }
}
