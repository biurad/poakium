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

use BiuradPHP\Loader\Aliases\AliasLoader;
use BiuradPHP\Loader\Annotations\AnnotationLoader;
use BiuradPHP\Loader\Composer\ComposerLoader;
use BiuradPHP\Loader\DataLoader;
use BiuradPHP\Loader\Files\FileLoader;
use BiuradPHP\Loader\Resources\UniformResourceLocator;
use Nette, BiuradPHP;
use Nette\Schema\Expect;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\ClassType as ClassTypeGenerator;

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
            'aliases' => Nette\Schema\Expect::arrayOf(Expect::string())
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('file'))
            ->setFactory(FileLoader::class)
            ->setArguments([$this->config['locators']['paths'], $this->config['locators']['excludes']])
        ;

        $builder->addDefinition($this->prefix('annotation'))
            ->setFactory(AnnotationLoader::class);

        $builder->addDefinition($this->prefix('data'))
            ->setFactory(DataLoader::class)
            ->setArguments([$this->config['data_path']])
        ;

        $builder->addDefinition($this->prefix('composer'))
            ->setFactory(ComposerLoader::class)
            ->setArguments([$this->config['composer_path']])
        ;

        $builder->addDefinition($this->prefix('locator'))
            ->setFactory(UniformResourceLocator::class)
            ->setArguments([$builder->parameters['path']['ROOT']])
            ->addSetup(
                'foreach (? as $scheme => [$path, $lookup]) { ?->addPath($scheme, $path, $lookup); }', [$this->config['resources'], '@self']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function afterCompile(ClassTypeGenerator $class): void
    {
        $init = $this->initialization ?? $class->getMethod('initialize');

        // For Runtime.
        $init->addBody(
            "// The loader class aliases.\n" .
                '$classAliases = new ?(?);' . "\n" .
                '$classAliases->register(); // Class alias registered.' .
                "\n\n",
            [new PhpLiteral(AliasLoader::class), $this->config['aliases']]
        );
    }
}
