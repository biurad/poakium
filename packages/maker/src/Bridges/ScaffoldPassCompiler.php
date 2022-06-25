<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Scaffold\Bridges;

use BiuradPHP\DependencyInjection\Compilers\ContainerBuilder;
use BiuradPHP\DependencyInjection\Interfaces\CompilerPassInterface;
use BiuradPHP\Scaffold\Commands\MakerCommand;
use BiuradPHP\Scaffold\Interfaces\MakerDeclareInterface;
use Symfony\Component\Console\Application;

class ScaffoldPassCompiler implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (null !== $container->getByType(Application::class)) {
            $findAll = \array_merge(
                $container->findByTag(ScaffoldExtension::MAKER_TAG),
                $container->findByType(MakerDeclareInterface::class)
            );

            // Find maker tags in container...
            foreach ($findAll as $id => $mixed) {
                $commandDefinition = $container->getDefinition($id);
                $class             = $commandDefinition->getEntity();
                $container->removeDefinition($id);

                $container->addDefinition('command.maker.' . \strtr($class::getCommandName(), ['-' => '_']))
                    ->setFactory(MakerCommand::class)
                    ->setArgument(4, $commandDefinition->getFactory())
                    ->addTag('console.command', \sprintf('make:%s', $class::getCommandName()));
            }
        }
    }
}
