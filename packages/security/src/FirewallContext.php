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

namespace BiuradPHP\Security;

use BiuradPHP\Security\Config\FirewallConfig;
use BiuradPHP\Security\Firewalls\ExceptionListener;
use BiuradPHP\Security\Firewalls\LogoutListener;

/**
 * This is a wrapper around the actual firewall configuration which allows us
 * to lazy load the context for one specific firewall only when we need it.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FirewallContext
{
    private $listeners;
    private $exceptionListener;
    private $logoutListener;
    private $config;

    public function __construct(iterable $listeners, ExceptionListener $exceptionListener = null, LogoutListener $logoutListener = null, FirewallConfig $config = null)
    {
        $this->listeners = $listeners;
        $this->exceptionListener = $exceptionListener;
        $this->logoutListener = $logoutListener;
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getListeners(): iterable
    {
        return $this->listeners;
    }

    public function getExceptionListener()
    {
        return $this->exceptionListener;
    }

    public function getLogoutListener()
    {
        return $this->logoutListener;
    }
}
