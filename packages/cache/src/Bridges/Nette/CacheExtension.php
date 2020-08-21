<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache\Bridges\Nette;

use Biurad\Cache\AdapterFactory;
use Biurad\Cache\Interfaces\CacheAdapterInterface;
use Nette;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;

class CacheExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'driver' => Nette\Schema\Expect::anyOf(Expect::string(), Expect::object(), null),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $cacheDefinition = $builder->addDefinition($this->prefix('psr16'))
            ->setFactory(Biurad\Cache\SimpleCache::class)
            ->setArguments([new Statement([AdapterFactory::class, 'createHandler'], ['array'])]);

        if (\extension_loaded('apcu')) {
            $cacheDefinition->setArgument(0, new Statement([AdapterFactory::class, 'createHandler'], ['apcu']));
        }

        $builder->addDefinition($this->prefix('psr6'))
            ->setFactory(Biurad\Cache\CacheItemPool::class);

        $builder->addAlias('cache', $this->prefix('psr16'));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
        $adapter = $this->config->driver;

        foreach ($builder->findByType(CacheAdapterInterface::class) as $name => $definition) {
            if ($adapter && $adapter !== $definition->getEntity()::getName()) {
                $builder->removeDefinition($name);

                continue;
            }

            $adapter = $definition->getFactory();
            $builder->removeDefinition($name);
        }

        if (null !== $adapter) {
            $builder->getDefinition($this->prefix('psr16'))
                ->setArgument(0, new Statement([AdapterFactory::class, 'createHandler'], [$adapter]));
        }
    }
}
