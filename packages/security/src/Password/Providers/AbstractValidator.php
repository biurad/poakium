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

namespace BiuradPHP\Security\Password\Providers;

use BiuradPHP\Security\Interfaces\PasswordValidatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractValidator implements PasswordValidatorInterface
{
    protected $config = [];

        /**
     * @var string
     */
    protected $error = '';

    /**
     * @var string
     */
    protected $suggestion = '';

    /**
     * Allows for setting a config file on the Validator.
     *
     * @param $config
     *
     * @return $this
     */
    public function setConfig(array $config): PasswordValidatorInterface
    {
        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function check(string $password, UserInterface $user = null): bool;

    /**
     * {@inheritdoc}
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function suggestion(): string
    {
        return $this->suggestion;
    }
}
