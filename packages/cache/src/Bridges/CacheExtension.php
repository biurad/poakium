<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  CacheManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/cachemanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Cache\Bridges;

use Nette, BiuradPHP;

class CacheExtension extends BiuradPHP\DependencyInjection\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        return Nette\Schema\Expect::structure([
            'enabled' => Nette\Schema\Expect::bool(),
            'driver' => Nette\Schema\Expect::string()->nullable(),
            'pools' => Nette\Schema\Expect::arrayOf('string|array'),
		])->otherItems('mixed');
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
	{
        $builder = $this->getContainerBuilder();

        CacheBridge::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->driver)
            ->getDefinition('cache.doctrine');

		$builder->addDefinition('cache')
            ->setFactory(BiuradPHP\Cache\SimpleCache::class)
            ->setArguments(['@cache.doctrine']);
    }
}
