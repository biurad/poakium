<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  Scaffolds Maker
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/scaffoldsmaker
 * @since     Version 0.1
 */

namespace BiuradPHP\Scaffold\Bridges;

use BiuradPHP\DependencyInjection\Concerns\ContainerBuilder;
use BiuradPHP\DependencyInjection\Interfaces\CompilerPassInterface;
use BiuradPHP\Scaffold\Bridges\ScaffoldExtension;
use BiuradPHP\Scaffold\Commands\MakerCommand;
use BiuradPHP\Scaffold\Interfaces\MakerDeclareInterface;
use Symfony\Component\Console\Application;

class ScaffoldPassCompiler implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (null !== $container->getByType(Application::class)) {
            $findAll = array_merge($container->findByTag(ScaffoldExtension::MAKER_TAG), $container->findByType(MakerDeclareInterface::class));

            // Find maker tags in container...
            foreach ($findAll as $id => $mixed) {
                $commandDefinition = $container->getDefinition($id);
                $class = $commandDefinition->getEntity();
                $container->removeDefinition($id);

                $container->addDefinition('command.maker.'. strtr($class::getCommandName(), ['-' => '_']))
                    ->setFactory(MakerCommand::class)
                    ->setArgument(4, $commandDefinition->getFactory())
                    ->addTag('console.command', sprintf('make:%s', $class::getCommandName()));
            }
        }
    }
}
