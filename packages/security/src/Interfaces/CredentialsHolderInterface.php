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

namespace BiuradPHP\Security\Interfaces;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * {@inheritdoc}
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface CredentialsHolderInterface extends UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Get the name of the unique identifier for the user.
     */
    public function getId(): string;

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @see Symfony\Component\Security\Core\Exception\DisabledException
     */
    public function isEnabled(): bool;

    /**
     * Get the user's first and last name, else empty string.
     */
    public function getFullName(): ?string;

    /**
     * Get the user's email address, else null.
     */
    public function getEmail(): ?string;
}
