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

namespace BiuradPHP\Cache\Bridges;

use BiuradPHP;
use Doctrine\Common\Cache\Cache;
use Nette;
use Nette\Schema\Expect;

class CacheExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'driver' => Nette\Schema\Expect::anyOf(
                'filesystem',
                'memory',
                'redis',
                'memcached',
                'memcache',
                'zenddata',
                'apcu',
                'xcache',
                'wincache',
                'sqlite'
            ),
            'pools' => Nette\Schema\Expect::structure([
                'filesystem' => Expect::structure([
                    'connection' => Expect::null(),
                    'extension'  => Expect::string(),
                ])->castTo('array'),
                'memory' => Expect::structure([
                    'connection' => Expect::null(),
                    'extension'  => Expect::string(),
                ])->castTo('array'),
                'redis' => Expect::structure([
                    'connection' => Expect::string(),
                ])->castTo('array'),
                'memcached' => Expect::structure([
                    'connection' => Expect::string(),
                ])->castTo('array'),
                'memcache' => Expect::structure([
                    'connection' => Expect::string(),
                ])->castTo('array'),
                'sqlite' => Expect::structure([
                    'connection' => Expect::string(),
                    'table'      => Expect::string(),
                ])->castTo('array'),
            ])->castTo('array'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        DoctrineCachePass::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->driver)
            ->getDefinition($this->prefix('doctrine'))
            ->setType(Cache::class);

        $builder->addDefinition($this->prefix('psr16'))
            ->setFactory(BiuradPHP\Cache\SimpleCache::class);

        $builder->addDefinition($this->prefix('psr6'))
            ->setFactory(BiuradPHP\Cache\CacheItemPool::class);

        $builder->addAlias('cache', $this->prefix('psr16'));
    }
}
