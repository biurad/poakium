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

namespace BiuradPHP\Security\Interfaces;

use Cycle\ORM\RepositoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface UsersRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve a user by their unique identifier and "email" address.
     *
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function findByEmail(string $username);

    /**
     * Retrieve a user by their "email" address or "username"
     *
     * @param string $usernameOrEmail
     *
     * @return UserInterface|null
     */
    public function findByUsernameOrEmail(string $usernameOrEmail);

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     *
     * @return UserInterface|null
     */
    public function findByCredentials(array $credentials);
}
