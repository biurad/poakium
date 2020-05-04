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

use BiuradPHP\Security\Firewalls\BasicAuthenticationListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;

/**
 * HttpBasicFactory creates services for HTTP basic authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HttpBasicFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config, string $userProvider)
    {
        $provider = 'security.authentication.provider.dao.'.$id;
        if (! $container->hasDefinition($provider)) {
            $container
                ->addDefinition($provider)
                ->setFactory(DaoAuthenticationProvider::class)
                ->setArgument(0, new Reference($userProvider))
                ->setArgument(1, new Reference('security.user_checker.'.$id))
                ->setArgument(2, $id)
                ->setAutowired(false)
            ;
        }

        // listener
        $listenerId = 'security.authentication.listener.basic.'.$id;
        $listener = $container->addDefinition($listenerId)->setFactory(BasicAuthenticationListener::class);
        $listener->setArgument(2, $id);
        $listener->setArgument(3, $config['realm']);
        $listener->addSetup('setSessionAuthenticationStrategy', [new Reference('security.authentication.session_strategy.'.$id)]);

        return [$provider, $listenerId];
    }

    public function getPosition()
    {
        return 'http';
    }

    public function getKey()
    {
        return 'http-basic';
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        return $node::structure([
            'provider' => $node::string(),
            'realm'    => $node::string()->default('Secured Area')
        ])->castTo('array');
    }
}
