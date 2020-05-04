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

namespace BiuradPHP\Security\Password;

use BiuradPHP\Security\Interfaces\PasswordValidatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;

class PasswordValidator implements PasswordValidatorInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var string
     */
    protected $suggestion = '';

    public function __construct(array $configurations)
    {
        $this->config = $configurations;
    }

    /**
     * Checks a password against all of the Validators specified.
     *
     * @param string $password
     * @param UserInterface   $user
     *
     * @return bool
     */
    public function check(string $password, UserInterface $user = null): bool
    {
        if (null === $user) {
            throw new UsernameNotFoundException('UserInterface must be provided for password validation.');
        }

        $password = trim($password);
        $valid = false;

        if(empty($password)) {
            $this->error = 'A valid Password is required.';

            return false;
        }

        /** @var PasswordValidatorInterface $class */
        foreach($this->config['validators'] as $class) {
            if (!$class instanceof PasswordValidatorInterface) {
                throw new AuthenticationServiceException('Password validators must be instance of ValidatorInterface');
            }

            $class->setConfig($this->config['options']);

            if ($class->check($password, $user) !== true) {
                $this->error = $class->error();
                $this->suggestion = $class->suggestion();
                $valid = false;

                break;
            }

            $valid = true;
        }

        return $valid;
    }

    /**
     * Returns the current error, as defined by validator
     * it failed to pass.
     *
     * @return mixed
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * Returns a string with any suggested fix
     * based on the validator it failed to pass.
     *
     * @return mixed
     */
    public function suggestion(): string
    {
        return $this->suggestion;
    }

    /**
     * Allows for setting a config file on the Validator.
     *
     * @param $config
     *
     * @return $this
     */
    public function setConfig(array $config): PasswordValidatorInterface
    {
        // TODO: Implement setConfig() method.

        return $this;
    }
}
