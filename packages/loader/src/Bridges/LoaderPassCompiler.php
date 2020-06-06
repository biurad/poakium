<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/biurad-loader
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Bridges;

use BiuradPHP\DependencyInjection\Concerns\ContainerBuilder;
use BiuradPHP\DependencyInjection\Interfaces\CompilerPassInterface;
use BiuradPHP\Loader\DelegatingLoader;
use BiuradPHP\Loader\Interfaces\LoaderInterface;

class LoaderPassCompiler implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $loader = $container->getDefinitionByType(DelegatingLoader::class);

        foreach ($container->findByType(LoaderInterface::class) as $name => $definition) {
            $newStatement = $definition->getFactory();
            $container->removeDefinition($name);

            $loader->addSetup('addLoader', [$newStatement]);
        }
    }
}
