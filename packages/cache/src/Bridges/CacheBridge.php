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

use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use BiuradPHP\Cache\Exceptions\CacheException;
use Doctrine, BiuradPHP, InvalidArgumentException;
use BiuradPHP\DependencyInjection\CompilerExtension;
use BiuradPHP\DependencyInjection\Interfaces\BridgeInterface;

class CacheBridge implements BridgeInterface
{
    private const DRIVERS = [
		'apcu'       => Doctrine\Common\Cache\ApcuCache::class,
		'array'      => Doctrine\Common\Cache\ArrayCache::class,
		'memcache'   =>  Doctrine\Common\Cache\MemcacheCache::class,
		'redis'      => Doctrine\Common\Cache\RedisCache::class,
        'xcache'     => Doctrine\Common\Cache\XcacheCache::class,
		'filesystem' => Doctrine\Common\Cache\FilesystemCache::class,
        'wincache'   => Doctrine\Common\Cache\WinCacheCache::class,
        'sqlite'     => Doctrine\Common\Cache\SQLite3Cache::class,
        'zenddata'   => Doctrine\Common\Cache\ZendDataCache::class,
        'database'   => BiuradPHP\Cache\Handlers\DatabaseCache::class,
    ];

    /** @var CompilerExtension */
	private $extension, $config, $prefix;

	/** @var string */
	private $default = 'array';

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
    }

    public static function of(CompilerExtension $extension): BridgeInterface
	{
		return new self($extension);
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

	public function setConfig($config): BridgeInterface
	{
		$this->config = $config;

		return $this;
	}

	public function withDefault(string $driver): BridgeInterface
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

        if ($this->config->enabled !== true) {
            $this->default = 'array';
        }

		$def = $builder->addDefinition($service)
            ->setFactory(self::DRIVERS[$this->default])
            ->setAutowired(true);

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
        $path = $builder->parameters['path']['TEMP'];

        $definition->setArguments([
            $path . DS . 'caches/biurad.caching',
            $filesystem['extension']
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
     * @return void
     */
    protected function createWincache(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
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
    protected function createZenddata(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
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
    protected function createArray(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
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

        $builder->addDefinition($this->prefix . '.connection')
            ->setFactory('BiuradPHP\Cache\Bridges\Connection::createRedis')
            ->setArguments([$host, $port]);

        $definition->addSetup('setRedis', ['@' . $this->prefix . '.connection']);
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
                "Sorry, It seems you server doesn't support mecached driver. If you think this is an error!. Enable mecached and try again"
            );
        }

        $memcache = $pools['memcache'];
        [$host, $port] = explode(':', $memcache['connection']);

        $builder->addDefinition($this->prefix . '.connection')
            ->setFactory('BiuradPHP\Cache\Bridges\Connection::createMemcache')
            ->setArguments([$host, $port]);

        $definition->addSetup('setMemcache', ['@' . $this->prefix . '.connection']);
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
        $path = $builder->parameters['path']['TEMP'];

        $filename = $sqlite['connection'];
        $table = $sqlite['table'];

        $builder->addDefinition($this->prefix . '.connection')
            ->setFactory('BiuradPHP\Cache\Bridges\Connection::createSqlite')
            ->setArguments([$path . DS . 'database' . DS . $filename]);

        $definition->setArguments(['@' . $this->prefix . '.connection', $table]);
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

        $builder->addDefinition($this->prefix . '.connection')
            ->setFactory('BiuradPHP\Cache\Bridges\Connection::createMysqli')
            ->setAutowired(false)
            ->setArguments([$db]);

        $definition->setArguments(['@' . $this->prefix . '.connection', $table, $options]);
    }
}
