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

use Nette\DI\Definitions\Statement;
use Nette\DI\Definitions\ServiceDefinition;
use BiuradPHP\Cache\Exceptions\CacheException;
use Doctrine, BiuradPHP, InvalidArgumentException;
use BiuradPHP\DependencyInjection\CompilerExtension;
use BiuradPHP\DependencyInjection\Concerns\ContainerBuilder;
use BiuradPHP\DependencyInjection\Interfaces\ContainerBridgeInterface;

use function method_exists;
use function ucfirst;
use function extension_loaded;
use function explode;
use function substr;
use function strlen;
use function strpos;
use function class_exists;

use const DIRECTORY_SEPARATOR;

class CachePass implements ContainerBridgeInterface
{
    private const DRIVERS = [
		'apcu'       => Doctrine\Common\Cache\ApcuCache::class,
		'array'      => Doctrine\Common\Cache\ArrayCache::class,
        'memcache'   => Doctrine\Common\Cache\MemcacheCache::class,
        'memcached'  => Doctrine\Common\Cache\MemcachedCache::class,
		'redis'      => Doctrine\Common\Cache\RedisCache::class,
        'xcache'     => Doctrine\Common\Cache\XcacheCache::class,
		'filesystem' => Doctrine\Common\Cache\FilesystemCache::class,
        'wincache'   => Doctrine\Common\Cache\WinCacheCache::class,
        'sqlite'     => Doctrine\Common\Cache\SQLite3Cache::class,
        'zenddata'   => Doctrine\Common\Cache\ZendDataCache::class,
        'database'   => BiuradPHP\Cache\Handlers\DatabaseCache::class,
    ];

    /** @var CompilerExtension */
	private $extension, $config;

	/** @var string */
	private $default = 'array';

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
    }

    public static function of(CompilerExtension $extension): ContainerBridgeInterface
	{
		return new self($extension);
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

	public function setConfig($config): ContainerBridgeInterface
	{
		$this->config = $config;

		return $this;
	}

	public function withDefault(string $driver): ContainerBridgeInterface
	{
		if (!isset(self::DRIVERS[$driver])) {
			throw new InvalidArgumentException(sprintf('Unsupported default cache driver "%s"', $driver));
		}

		$this->default = $driver;

		return $this;
	}

    /**
     * @param string $service
     *
     * @return ServiceDefinition
     */
	public function getDefinition(string $service)
	{
        $builder = $this->extension->getContainerBuilder();

		$def = $builder->addDefinition($service)
            ->setFactory(self::DRIVERS[$this->default]);

        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
		if (isset($this->default)) {
            $pools = $this->config->pools;

            if (method_exists($this, $method = 'create'.ucfirst($this->default))) {
                $this->$method($def, $builder, $pools);
            }
		}

		return $def;
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createFilesystem(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $filesystem = $pools['filesystem'];
        $path = $builder->getParameter('path.TEMP');

        $definition->setArguments([
            $path . DIRECTORY_SEPARATOR . 'caches/biurad.caching', $filesystem['extension']
        ]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createApcu(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
        if (! extension_loaded('apcu')) {
            throw new CacheException(
                "Sorry, It seems you server doesn't support apcu driver. If you think this is an error!. Enable apcu and try again"
            );
        }
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @deprecated
     *
     * @return void
     */
    protected function createXcache(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
        throw new CacheException(
            "Sorry, This driver has been deprecated, and will be removed in future development", E_DEPRECATED
        );
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createRedis(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!class_exists(\Redis::class)) {
            throw new CacheException(
                "Sorry, It seems you server doesn't support redis driver. If you think this is an error!. Enable redis and try again"
            );
        }

        $redis = $pools['redis'];
        [$host, $port] = explode(':', $redis['connection']);

        $definition->addSetup('setRedis', [new Statement([Connection::class, 'createRedis'], [$host, $port])]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createMemcache(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!class_exists(\Memcache::class)) {
            throw new CacheException(
                "Sorry, It seems you server doesn't support memcache driver. If you think this is an error!. Enable mecached and try again"
            );
        }

        $memcache = $pools['memcache'];
        [$host, $port] = explode(':', $memcache['connection']);

        $definition->addSetup('setMemcache', [new Statement([Connection::class, 'createMemcache'], [$host, $port])]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createMemcached(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!class_exists(\Memcached::class)) {
            throw new CacheException(
                "Sorry, It seems you server doesn't support memcached driver. If you think this is an error!. Enable mecached and try again"
            );
        }

        $memcached = $pools['memcache'];
        [$host, $port] = explode(':', $memcached['connection']);

        $definition->addSetup('setMemcache', [new Statement([Connection::class, 'createMemcached'], [$host, $port, $memcached['options']])]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createSqlite(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $sqlite = $pools['sqlite'];
        $path = $builder->getParameter('path.TEMP');

        $filename = $sqlite['connection'];
        $table = $sqlite['table'];

        $filePath = "{$path}/database/{$filename}";
        if (false !== strpos($filename, $path)) {
            $filePath = $filename;
            if (false === strpos($filePath, 'database')) {
                $filePath = "{$path}/database/" . substr($filePath, strlen($path) + 1);
            }
        }

        $definition->setArguments([new Statement([Connection::class, 'createSqlite'], [$filePath]), $table]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createDatabase(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $mysqli = $pools['database'];

        $options = $mysqli['options'];
        [$db, $table] = explode(':', $mysqli['collection']);

        $definition->setArguments([new Statement([Connection::class, 'createMysqli'], [$db]), $table, $options]);
    }
}
