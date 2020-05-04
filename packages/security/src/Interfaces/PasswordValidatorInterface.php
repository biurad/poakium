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

/**
 * Interface ValidatorInterface
 */
interface PasswordValidatorInterface
{
    /**
     * Allows for setting a config file on the Validator.
     *
     * @param $config
     *
     * @return $this
     */
    public function setConfig(array $config): PasswordValidatorInterface;

    /**
     * Checks the password and returns true/false
     * if it passes muster. Must return either true/false.
     * True means the password passes this test and
     * the password will be passed to any remaining validators.
     * False will immediately stop validation process
     *
     * @param string $password
     * @param UserInterface $user
     *
     * @return bool
     */
    public function check(string $password, UserInterface $user = null): bool;

    /**
     * Returns the error string that should be displayed to the user.
     *
     * @return string
     */
    public function error(): string;

    /**
     * Returns a suggestion that may be displayed to the user
     * to help them choose a better password. The method is
     * required, but a suggestion is optional. May return
     * an empty string instead.
     *
     * @return string
     */
    public function suggestion(): string;
}
