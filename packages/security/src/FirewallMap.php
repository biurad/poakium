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

use BiuradPHP\Http\Interfaces\RequestMatcherInterface;
use BiuradPHP\Security\Config\FirewallConfig;
use BiuradPHP\Security\Firewalls\ExceptionListener;
use BiuradPHP\Security\Firewalls\LogoutListener;
use BiuradPHP\Security\Interfaces\FirewallMapInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * FirewallMap allows configuration of different firewalls for specific parts
 * of the website.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FirewallMap implements FirewallMapInterface
{
    private $container;
    private $map = [];

    public function __construct(ContainerInterface $container, iterable $map)
    {
        $this->container = $container;
        $this->map = $map;
    }

    public function add(RequestMatcherInterface $requestMatcher = null, array $listeners = [], ExceptionListener $exceptionListener = null, LogoutListener $logoutListener = null)
    {
        $this->map[] = [$requestMatcher, $listeners, $exceptionListener, $logoutListener];
    }

    public function getListeners(Request $request)
    {
        $context = $this->getFirewallContext($request);

        if (null === $context) {
            return [[], null, null];
        }

        return [$context->getListeners(), $context->getExceptionListener(), $context->getLogoutListener()];
    }

    /**
     * @param Request $request
     * @return FirewallConfig|null
     */
    public function getFirewallConfig(Request $request)
    {
        $context = $this->getFirewallContext($request);

        if (null === $context) {
            return null;
        }

        return $context->getConfig();
    }

    private function getFirewallContext(Request $request): ?FirewallContext
    {
        $attributes = $request->getAttributes();

        if (isset($attributes['_firewall_context'])) {
            $storedContextId = $request->getAttribute('_firewall_context');
            foreach ($this->map as $contextId => $requestMatcher) {
                if ($contextId === $storedContextId) {
                    return $this->container->get($contextId);
                }
            }

            $request->withoutAttribute('_firewall_context');
        }

        foreach ($this->map as $contextId => $requestMatcher) {
            if (null === $requestMatcher || $requestMatcher->matches($request)) {
                $request->withAttribute('_firewall_context', $contextId);

                return $this->container->get($contextId);
            }
        }

        return null;
    }
}
