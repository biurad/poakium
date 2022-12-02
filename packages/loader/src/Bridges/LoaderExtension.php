<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Loader\Bridges;

use BiuradPHP\Loader\Files\DataLoader;
use BiuradPHP\Loader\LoaderManager;
use BiuradPHP\Loader\Locators\AliasLocator;
use BiuradPHP\Loader\Locators\ComposerLocator;
use BiuradPHP\Loader\Locators\FileLocator;
use BiuradPHP\Loader\Locators\UniformResourceLocator;
use Nette;
use Nette\DI\Container;
use Nette\PhpGenerator\ClassType as ClassTypeGenerator;
use Nette\Schema\Expect;

class LoaderExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'locators' => Nette\Schema\Expect::structure([
                'paths' => Expect::listOf('string')->before(function ($value) {
                    return \is_string($value) ? [$value] : $value;
                }),
                'excludes' => Expect::listOf('string')->before(function ($value) {
                    return \is_string($value) ? [$value] : $value;
                }),
            ])->castTo('array'),
            'resources' => Nette\Schema\Expect::arrayOf(Expect::array()->before(function ($value) {
                return \is_string($value) ? ['', $value] : $value;
            })),
            'data_path'     => Nette\Schema\Expect::string(),
            'composer_path' => Nette\Schema\Expect::string()->nullable(),
            'aliases'       => Nette\Schema\Expect::arrayOf(Expect::string()),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('file'))
            ->setFactory(FileLocator::class)
            ->setArguments([$this->config['locators']['paths'], $this->config['locators']['excludes']])
        ;

        $builder->addDefinition($this->prefix('loader'))
            ->setFactory(LoaderManager::class);

        $builder->addDefinition($this->prefix('data'))
            ->setFactory(DataLoader::class)
            ->setArguments([$this->config['data_path']])
        ;

        $builder->addDefinition($this->prefix('composer'))
            ->setFactory(ComposerLocator::class)
            ->setArguments([$this->config['composer_path']])
        ;

        $builder->addDefinition($this->prefix('alias'))
            ->setFactory(AliasLocator::class, [$this->config['aliases']]);

        $locator = $builder->addDefinition($this->prefix('locator'))
            ->setFactory(UniformResourceLocator::class)
            ->setArguments([$builder->parameters['path']['ROOT']]);

        foreach ($this->config['resources'] as $scheme => [$path, $lookup]) {
            $locator->addSetup('addPath', [$scheme, $path, $lookup]);
        }

        $builder->addAlias('locator', $this->prefix('locator'));
    }

    /**
     * {@inheritDoc}
     */
    public function afterCompile(ClassTypeGenerator $class): void
    {
        $init = $this->initialization ?? $class->getMethod('initialize');

        if (empty($this->config['aliases'])) {
            return;
        }

        // For Runtime.
        $init->addBody(
            '$this->?()->register(); // Class alias registered.' . "\n\n",
            [Container::getMethodName($this->prefix('alias'))]
        );
    }
}
