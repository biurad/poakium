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
use Biurad\Security\Interfaces\RequireTokenInterface;
use Psr\Http\Message\ServerRequestInterface;
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
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FormLoginAuthenticator implements AuthenticatorInterface, RequireTokenInterface
{
    private UserProviderInterface $provider;
    private ?TokenInterface $token = null;
    private PasswordHasherFactoryInterface $hasherFactory;
    private ?RememberMeHandler $rememberMeHandler;
    private string $userParameter, $passwordParameter;

    public function __construct(
        UserProviderInterface $provider,
        PasswordHasherFactoryInterface $hasherFactory,
        RememberMeHandler $rememberMeHandler = null,
        string $userParameter = '_identifier',
        string $passwordParameter = '_password'
    ) {
        $this->provider = $provider;
        $this->hasherFactory = $hasherFactory;
        $this->rememberMeHandler = $rememberMeHandler;
        $this->userParameter = $userParameter;
        $this->passwordParameter = $passwordParameter;
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
        return 'POST' === $request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials, $firewallName): ?TokenInterface
    {
        if (empty($credentials)) {
            return null;
        }

        $username = $credentials[$this->userParameter] ?? null;
        $password = $credentials[$this->passwordParameter] ?? null;

        if (empty($username) xor empty($password)) {
            throw new BadCredentialsException('The presented username or password cannot be empty.');
        }

        if (!\is_string($username) || \strlen($username) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        $user = $this->provider->loadUserByIdentifier($username);

        if ($user instanceof PasswordAuthenticatedUserInterface) {
            $userHasher = $this->hasherFactory->getPasswordHasher($user);

            if (!$userHasher->verify($user->getPassword(), $password)) {
                throw new BadCredentialsException('The presented password is invalid.');
            }

            if ($this->provider instanceof PasswordUpgraderInterface && $userHasher->needsRehash($password)) {
                $this->provider->upgradePassword($user, $userHasher->hash($password));
            }
        }

        if ($request->hasHeader('X-Switch-User') && $oldToken = $this->token) {
            if ($username === $oldToken->getUserIdentifier()) {
                throw new AuthenticationException('The current user is already authenticated.');
            }
            $token = new SwitchUserToken($user, $firewallName, $user->getRoles(), $oldToken, (string) $request->getUri());
        } else {
            $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        }

        if (null !== $this->rememberMeHandler) {
            $rememberMe = $credentials[$this->rememberMeHandler->getParameterName()] ?? false;

            if ('true' === $rememberMe || 'on' === $rememberMe || '1' === $rememberMe || 'yes' === $rememberMe || true === $rememberMe) {
                $rememberMeCookie = $this->rememberMeHandler->createRememberMeCookie($token->getUser());
                $token->setAttribute(RememberMeHandler::REMEMBER_ME, $rememberMeCookie->withSecure('https' === $request->getUri()->getScheme()));
            }
        }

        return $token;
    }
}
