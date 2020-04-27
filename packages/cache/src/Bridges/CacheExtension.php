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
            'driver' => Nette\Schema\Expect::string()->required(),
            'pools' => Nette\Schema\Expect::arrayOf('string|array'),
		])->otherItems('mixed');
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
	{
        $builder = $this->getContainerBuilder();

        CachePass::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->driver)
            ->getDefinition($this->prefix('doctrine'))
            ->setType(\Doctrine\Common\Cache\Cache::class)
        ;

        $builder->addDefinition($this->prefix('psr'))
            ->setFactory(BiuradPHP\Cache\SimpleCache::class)
        ;

		$builder->addDefinition($this->prefix('factory'))
            ->setFactory(BiuradPHP\Cache\Caching::class)
        ;

        $builder->addAlias('cache', $this->prefix('factory'));
    }
}
