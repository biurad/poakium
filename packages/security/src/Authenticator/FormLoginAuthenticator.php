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

use Biurad\Http\Request;
use Biurad\Security\Handler\RememberMeHandler;
use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This authenticator authenticates a user via form request.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FormLoginAuthenticator implements AuthenticatorInterface
{
    private UserProviderInterface $provider;
    private ?TokenInterface $token = null;
    private PasswordHasherFactoryInterface $hasherFactory;
    private ?RememberMeHandler $rememberMeHandler;
    private ?SessionInterface $session;
    private bool $eraseCredentials;

    public function __construct(
        UserProviderInterface $provider,
        PasswordHasherFactoryInterface $hasherFactory,
        RememberMeHandler $rememberMeHandler = null,
        SessionInterface $session = null,
        bool $eraseCredentials = true
    ) {
        $this->provider = $provider;
        $this->hasherFactory = $hasherFactory;
        $this->rememberMeHandler = $rememberMeHandler;
        $this->session = $session;
        $this->eraseCredentials = $eraseCredentials;
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
        if (null !== $this->token) {
            // allows a user to impersonate another one temporarily (like the Unix su command).
            return $request->hasHeader('AUTH-SWITCH-USER');
        }

        return 'POST' === $request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials): ?TokenInterface
    {
        if (empty($credentials)) {
            return null;
        }

        $username = $credentials['_identifier'] ?? $credentials['identifier'] ?? null;
        $password = $credentials['_password'] ?? $credentials['password'] ?? null;

        if (empty($username) xor empty($password)) {
            throw new BadCredentialsException('The presented username or password cannot be empty.');
        }

        if (!\is_string($username) || \strlen($username) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        if (null !== $this->session) {
            $this->session->set(Security::LAST_USERNAME, $username);
        }

        if ($request instanceof Request) {
            $request->getRequest()->attributes->set(Security::LAST_USERNAME, $username);
        }

        $user = $this->provider->loadUserByIdentifier($username);
        $userHasher = $this->hasherFactory->getPasswordHasher($user);

        if (!$userHasher->verify($user->getPassword(), $password)) {
            throw new BadCredentialsException('The presented password is invalid.');
        }

        if ($this->provider instanceof PasswordUpgraderInterface && $userHasher->needsRehash($password)) {
            $this->provider->passwordUpgrade($user, $userHasher->hash($password));
        }

        if ($this->eraseCredentials) {
            $user->eraseCredentials();
        }

        if ($request->hasHeader('AUTH-SWITCH-USER')) {
            $token = $this->token;

            if ($token && $username !== $token->getUserIdentifier()) {
                $token = new SwitchUserToken($user, 'main', $user->getRoles(), $token, (string) $request->getUri());
            }
        }

        $token ??= new UsernamePasswordToken($user, 'main', $user->getRoles());

        if (null !== $this->rememberMeHandler) {
            $token = $this->rememberMeCookie($this->rememberMeHandler, $token, $request, $credentials[$this->rememberMeHandler->getParameterName()] ?? false);
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (null !== $this->session) {
            $this->session->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return null;
    }

    /**
     * @param string|int|bool $rememberMe
     */
    protected function rememberMeCookie(RememberMeHandler $rememberMeHandler, TokenInterface $token, ServerRequestInterface $request, $rememberMe): TokenInterface
    {
        if ('true' === $rememberMe || 'on' === $rememberMe || '1' === $rememberMe || 'yes' === $rememberMe || true === $rememberMe) {
            $rememberMeCookie = $rememberMeHandler->createRememberMeCookie($token->getUser());
            $token->setAttribute(RememberMeHandler::REMEMBER_ME, $rememberMeCookie->withSecure('https' === $request->getUri()->getScheme()));
        }

        return $token;
    }
}
