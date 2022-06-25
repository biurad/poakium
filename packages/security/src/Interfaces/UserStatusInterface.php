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

namespace Biurad\Security\Interfaces;

/**
 * Interface implemented to hold credentials of a user's status.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface UserStatusInterface
{
    /**
     * Get the date of when the user was created.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the date of when the user was last updated.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Get the date of when the user was last logged in.
     */
    public function getLastLogin(): ?\DateTimeInterface;

    /**
     * Get the location of the user's last login. (eg: ip address).
     */
    public function getLocation(): ?string;

    /**
     * Check if the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @see Symfony\Component\Security\Core\Exception\DisabledException
     */
    public function isLocked(): bool;
}
