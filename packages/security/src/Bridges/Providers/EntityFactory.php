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

namespace BiuradPHP\Security\Bridges\Providers;

use BiuradPHP\Security\Interfaces\UserProviderFactoryInterface;
use Nette\DI\ContainerBuilder;

/**
 * EntityFactory creates services for CycleORM user provider.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class EntityFactory implements UserProviderFactoryInterface
{
    private $key;
    private $providerId;

    public function __construct(string $key, string $providerId)
    {
        $this->key = $key;
        $this->providerId = $providerId;
    }

    public function create(ContainerBuilder $container, string $id, array $config)
    {
        $container
            ->addDefinition($id)
            ->setFactory($this->providerId)
            ->setArgument(1, $config['class'])
            ->setArgument(2, $config['property'])
        ;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        return $node::structure([
            'class'      => $node::string()->required()->assert('class_exists'),
            'property'  => $node::string()->nullable(),
        ])->castTo('array');
    }
}
