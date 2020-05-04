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

use Nette\DI\ContainerBuilder;
use BiuradPHP\Security\Firewalls\AnonymousAuthenticationListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class AnonymousFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config, string $userProviderId = null)
    {
        if (!isset($config['secret'])) {
            $config['secret'] = 'anonymous';
        }

        $listenerId = 'security.authentication.listener.anonymous.'.$id;
        $container
            ->addDefinition($listenerId)->setFactory(AnonymousAuthenticationListener::class)
            ->setArgument(1, $config['secret'])
        ;

        $providerId = 'security.authentication.provider.anonymous.'.$id;
        $container
            ->addDefinition($providerId)->setFactory(AnonymousAuthenticationProvider::class)
            ->setArgument(0, $config['secret'])->setAutowired(false);
        ;

        return [$providerId, $listenerId];
    }

    public function getPosition()
    {
        return 'anonymous';
    }

    public function getKey()
    {
        return 'anonymous';
    }

    public function addConfiguration(\Nette\Schema\Expect $builder)
    {
        return $builder::structure([
            'lazy'      => $builder::bool(false)->before(function ($value) {
                return 'lazy' === $value ? true : $value;
            }),
            'secret'    => $builder::string()->default('anonymous'),
        ])->before(function ($value) {
            return is_bool($value) ? ['lazy' => true] : $value;
        })->castTo('array');
    }
}
