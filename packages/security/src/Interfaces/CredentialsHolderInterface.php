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

use Symfony\Component\Security\Core\User\UserInterface;

interface CredentialsHolderInterface extends UserInterface
{
    public function getPlainPassword(): ?string;

    public function setPlainPassword(?string $plainPassword): void;

    public function setPassword(?string $encodedPassword): void;

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getId();
}
