<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\User;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Security\Event\UserEvent;
use BiuradPHP\Security\Interfaces\CredentialsHolderInterface;
use BiuradPHP\Security\Interfaces\PasswordValidatorInterface;
use BiuradPHP\Security\Interfaces\UserLoginInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserFirewall implements UserLoginInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var UserCheckerInterface */
    private $userChecker;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserCheckerInterface $userChecker,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userChecker = $userChecker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Check's if the user already exists.
     *
     * @param UserProviderInterface $provider
     * @param string $property
     * @return CredentialsHolderInterface|UserInterface|string user instance or string of error message
     */
    public function checkUserExistence(UserProviderInterface $provider, string $property = 'username')
    {
        try {
            return $provider->loadUserByUsername($property);
        } catch (UsernameNotFoundException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Handles the password verfication and returns user with a plain password.
     *
     * A validation helper method to check if the password in $_POST request
     * will pass all of the validators currently defined.
     *
     * @param PasswordValidatorInterface $validator
     * @param CredentialsHolderInterface $user
     * @param string $password
     * @return CredentialsHolderInterface|string user instance or string of error message
     */
    public function checkUserPassword(PasswordValidatorInterface $validator, CredentialsHolderInterface $user, string $password)
    {
        // Checks if password is Valid
        if (! $validator->check($password, $user)) {
           return $validator->error();
        }

        // Then proceed to set the password.
        $user->setPlainPassword($password);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function login(UserInterface $user, ?string $firewallName = null): void
    {
        $firewallName = $firewallName ?? 'main';

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        $token = $this->createToken($user, $firewallName);
        if (!$token->isAuthenticated()) {
            throw new AuthenticationException('Unauthenticated token');
        }

        $this->tokenStorage->setToken($token);
        $this->eventDispatcher->dispatch(new UserEvent($user));
    }

    protected function createToken(UserInterface $user, string $firewallName): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());
    }
}
