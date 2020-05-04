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


use Nette\DI\ContainerBuilder;
use BiuradPHP\Security\Interfaces\UserProviderFactoryInterface;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

/**
 * InMemoryFactory creates services for the memory provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class InMemoryFactory implements UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config)
    {
        $definition = $container->addDefinition($id)->setFactory(InMemoryUserProvider::class);
        $defaultPassword = 'password';
        $users = [];

        foreach ($config as $username => $user) {
            $users[$username] = ['password' => null !== $user['password'] ? (string) $user['password'] : $defaultPassword, 'roles' => $user['roles']];
        }

        $definition->setArguments([$users]);
    }

    public function getKey()
    {
        return 'memory';
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        return $node::arrayOf(
            $node::structure([
                'password'  => $node::string()->nullable(),
                'roles'     => $node::array(),
            ])->castTo('array')
        );
    }
}
