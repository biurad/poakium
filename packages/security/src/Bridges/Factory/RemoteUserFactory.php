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

use BiuradPHP\Security\Firewalls\RemoteUserAuthenticationListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider;

/**
 * RemoteUserFactory creates services for REMOTE_USER based authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Maxime Douailin <maxime.douailin@gmail.com>
 */
class RemoteUserFactory implements SecurityFactoryInterface
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

        $listenerId = 'security.authentication.listener.remote_user.'.$id;
        $listener = $container->addDefinition($listenerId)->setFactory(RemoteUserAuthenticationListener::class);
        $listener->setArgument(2, $id);
        $listener->setArgument(3, $config['user']);
        $listener->addSetup('setSessionAuthenticationStrategy', [new Reference('security.authentication.session_strategy.'.$id)]);

        return [$providerId, $listenerId];
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'remote-user';
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        return $node::structure([
            'provider' => $node::string(),
            'user'     => $node::string()->default('REMOTE_USER')
        ])->castTo('array');
    }
}
