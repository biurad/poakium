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

namespace BiuradPHP\Security\Bridges\Factory;

use BiuradPHP\Security\Firewalls\X509AuthenticationListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider;

/**
 * X509Factory creates services for X509 certificate authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class X509Factory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config, string $userProvider)
    {
        $providerId = 'security.authentication.provider.pre_authenticated.'.$id;
        if (! $container->hasDefinition($providerId)) {
            $container
                ->addDefinition($providerId)
                ->setFactory(PreAuthenticatedAuthenticationProvider::class)
                ->setArgument(0, new Reference($userProvider))
                ->setArgument(1, new Reference('security.user_checker.'.$id))
                ->setArgument(2, $id)
                ->setAutowired(false)
            ;
        }

        // listener
        $listenerId = 'security.authentication.listener.x509.'.$id;
        $listener = $container->addDefinition($listenerId)->setFactory(X509AuthenticationListener::class);
        $listener->setArgument(2, $id);
        $listener->setArgument(3, $config['user']);
        $listener->setArgument(4, $config['credentials']);
        $listener->addSetup('setSessionAuthenticationStrategy', [new Reference('security.authentication.session_strategy.'.$id)]);

        return [$providerId, $listenerId];
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'x509';
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        return $node::structure([
            'provider'      => $node::string(),
            'user'          => $node::string()->default('SSL_CLIENT_S_DN_Email'),
            'credentials'   => $node::string()->default('SSL_CLIENT_S_DN')
        ])->castTo('array');
    }
}
