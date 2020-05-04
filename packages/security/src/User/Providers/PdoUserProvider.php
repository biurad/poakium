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

namespace BiuradPHP\Security\User\Providers;

use BiuradPHP\Security\Interfaces\UsersRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Wrapper around a Pdo.
 *
 * Provides provisioning for Pdo model users.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class PdoUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private $registry;
    private $classOrAlias;
    private $property;

    public function __construct(\Pdo $registry, string $classOrAlias, string $property = null)
    {
        $this->registry = $registry;
        $this->classOrAlias = $classOrAlias;
        $this->property = $property;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername(string $username)
    {
        $repository = $this->getRepository();
        if (null !== $this->property) {
            $user = $repository->query([$this->property => $username]);
        } else {
            if (!$repository instanceof UsersRepositoryInterface) {
                throw new \InvalidArgumentException(sprintf('You must either make the "%s" entity Cycle Repository ("%s") implement "BiuradPHP\Security\Interfaces\UsersRepositoryInterface" or set the "property" option in the corresponding entity provider configuration.', $this->classOrAlias, \get_class($repository)));
            }

            $user = $repository->query($username);
        }

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $class = $this->getClass();
        if (!$user instanceof $class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $repository = $this->getRepository();
        $refreshedUser = $repository->query($user->getId());

        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($user->getId())));
        }

        return $refreshedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class)
    {
        return $class === $this->getClass() || is_subclass_of($class, $this->getClass());
    }

    /**
     * {@inheritdoc}
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $class = $this->getClass();
        if (!$user instanceof $class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $repository = $this->getRepository();
        if ($repository instanceof PasswordUpgraderInterface) {
            $repository->upgradePassword($user, $newEncodedPassword);
        }
    }

    private function getRepository(): \PDO
    {
        return $this->registry;
    }

    private function getClass(): string
    {
        return $this->classOrAlias;
    }
}
