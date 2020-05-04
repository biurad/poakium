<?php /** @noinspection PhpUndefinedMethodInspection */

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

use Doctrine\Common\Cache\Cache;
use Nette, BiuradPHP;
use Nette\Schema\Expect;

class CacheExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        return Nette\Schema\Expect::structure([
            'driver'    => Nette\Schema\Expect::anyOf(
                'filesystem', 'memory', 'redis',
                'memcached', 'memcache', 'zenddata',
                'apcu', 'xcache', 'wincache', 'sqlite'
            ),
            'pools'     => Nette\Schema\Expect::structure([
                'filesystem'    => Expect::structure([
                    'connection'    => Expect::null(),
                    'extension'     => Expect::string()
                ])->castTo('array'),
                'memory'    => Expect::structure([
                    'connection'    => Expect::null(),
                    'extension'     => Expect::string()
                ])->castTo('array'),
                'redis'    => Expect::structure([
                    'connection'    => Expect::string()
                ])->castTo('array'),
                'memcached'    => Expect::structure([
                    'connection'    => Expect::string()
                ])->castTo('array'),
                'memcache'    => Expect::structure([
                    'connection'    => Expect::string()
                ])->castTo('array'),
                'sqlite'    => Expect::structure([
                    'connection'    => Expect::string(),
                    'table'     => Expect::string()
                ])->castTo('array'),
            ])->castTo('array'),
		]);
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
	{
        $builder = $this->getContainerBuilder();

        DoctrineCachePass::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->driver)
            ->getDefinition($this->prefix('doctrine'))
            ->setType(Cache::class)
        ;

        $builder->addDefinition($this->prefix('psr'))
            ->setFactory(BiuradPHP\Cache\SimpleCache::class)
        ;
    }
}
