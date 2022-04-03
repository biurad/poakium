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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This authenticator authenticates a user via form request.
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

        $username = $credentials['_username'] ?? $credentials['username'] ?? null;
        $password = $credentials['_password'] ?? $credentials['password'] ?? null;

        if (empty($username) xor empty($password)) {
            throw new BadCredentialsException('The presented username or password cannot be empty.');
        }

        if (!\is_string($username) || \strlen($username) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        $this->session->set(Security::LAST_USERNAME, $username);
        $user = $this->provider->loadUserByIdentifier($username);

        if ($request instanceof Request) {
            $request->getRequest()->attributes->set(Security::LAST_USERNAME, $username);
        }

        if ($this->eraseCredentials) {
            $user->eraseCredentials();
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw new \LogicException(\sprintf('Class "%s" must implement "%s" for using password-based authentication.', \get_debug_type($user), PasswordAuthenticatedUserInterface::class));
        }

        $userHasher = $this->hasherFactory->getPasswordHasher($user);

        if (!$userHasher->verify($user->getPassword(), $password)) {
            throw new BadCredentialsException('The presented password is invalid.');
        }

        if ($this->provider instanceof PasswordUpgraderInterface && $userHasher->needsRehash($password)) {
            $this->provider->passwordUpgrade($user, $userHasher->hash($password));
        }

        if (null !== $previousToken = $this->tokenStorage->getToken()) {
            $token = new SwitchUserToken($user, 'main', $user->getRoles(), $previousToken, (string) $request->getUri());
        } else {
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        }

        if (null !== $this->rememberMeHandler && !empty($rememberMe = $credentials[$this->rememberMeHandler->getParameterName()] ?? null)) {
            $rememberMe = 'true' === $rememberMe || 'on' === $rememberMe || '1' === $rememberMe || 'yes' === $rememberMe || true === $rememberMe;

            if ($rememberMe) {
                $token->setAttribute(RememberMeHandler::REMEMBER_ME, $this->rememberMeHandler->createRememberMeCookie($user, 'https' === $request->getUri()->getScheme()));
            }
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        return null;
    }
}
