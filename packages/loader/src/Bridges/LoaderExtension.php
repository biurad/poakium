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

use BiuradPHP\DependencyInjection\Concerns\Compiler;
use BiuradPHP\Loader\Locators\AliasLocator;
use BiuradPHP\Loader\AliasLoader;
use BiuradPHP\Loader\ConfigFileLoader;
use BiuradPHP\Loader\DelegatingLoader;
use BiuradPHP\Loader\DirectoryLoader;
use BiuradPHP\Loader\Files\DataLoader;
use BiuradPHP\Loader\GlobFileLoader;
use BiuradPHP\Loader\Interfaces\LoaderExtensionInterface;
use BiuradPHP\Loader\Locators\AnnotationLocator;
use BiuradPHP\Loader\Locators\ComposerLocator;
use BiuradPHP\Loader\Locators\FileLocator;
use BiuradPHP\Loader\Locators\UniformResourceLocator;
use Doctrine\Common\Annotations\Reader;
use Nette, BiuradPHP;
use Nette\DI\Container;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\PhpGenerator\ClassType as ClassTypeGenerator;

class LoaderExtension extends Nette\DI\CompilerExtension implements LoaderExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'locators' => Nette\Schema\Expect::structure([
                'paths' => Expect::listOf('string')->before(function ($value) {
                    return is_string($value) ? [$value] : $value;
                }),
                'excludes' => Expect::listOf('string')->before(function ($value) {
                    return is_string($value) ? [$value] : $value;
                }),
            ])->castTo('array'),
            'resources' => Nette\Schema\Expect::arrayOf(Expect::array()->before(function ($value) {
                return is_string($value) ? ['', $value] : $value;
            })),
            'data_path' => Nette\Schema\Expect::string(),
            'composer_path' => Nette\Schema\Expect::string()->nullable(),
            'aliases' => Nette\Schema\Expect::arrayOf(Expect::string()),
            'loaders' => Nette\Schema\Expect::listOf(Expect::object()->before(function ($value) {
                return is_string($value) ? new Statement($value) : $value;
            })),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('file'))
            ->setFactory(FileLocator::class)
            ->setArguments([$this->config['locators']['paths'], $this->config['locators']['excludes']])
        ;

        $builder->addDefinition($this->prefix('loader'))
            ->setFactory(DelegatingLoader::class)
            ->setArguments([
                array_merge([
                    new Statement(AliasLoader::class),
                    new Statement(ConfigFileLoader::class),
                    new Statement(DirectoryLoader::class),
                    new Statement(GlobFileLoader::class),
                ], $this->config['loaders'])
            ]);

        if (class_exists(Reader::class)) {
            $builder->addDefinition($this->prefix('annotation'))
                ->setFactory(AnnotationLocator::class);
        }

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
     * {@inheritdoc}
     */
    public function addCompilerPasses(Compiler &$compiler): void
    {
        $compiler->addPass(new LoaderPassCompiler());
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
            '$this->?()->register(); // Class alias registered.'."\n\n",
            [Container::getMethodName($this->prefix('alias'))]
        );
    }
}
