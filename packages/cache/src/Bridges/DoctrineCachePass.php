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

use BiuradPHP\Cache\Exceptions\CacheException;
use Doctrine;
use InvalidArgumentException;
use Memcache;
use Memcached;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Redis;

class DoctrineCachePass
{
    private const DRIVERS = [
        'apcu'       => Doctrine\Common\Cache\ApcuCache::class,
        'array'      => Doctrine\Common\Cache\ArrayCache::class,
        'memory'     => Doctrine\Common\Cache\PhpFileCache::class,
        'memcache'   => Doctrine\Common\Cache\MemcacheCache::class,
        'memcached'  => Doctrine\Common\Cache\MemcachedCache::class,
        'redis'      => Doctrine\Common\Cache\RedisCache::class,
        'xcache'     => Doctrine\Common\Cache\XcacheCache::class,
        'filesystem' => Doctrine\Common\Cache\FilesystemCache::class,
        'wincache'   => Doctrine\Common\Cache\WinCacheCache::class,
        'sqlite'     => Doctrine\Common\Cache\SQLite3Cache::class,
        'zenddata'   => Doctrine\Common\Cache\ZendDataCache::class,
    ];

    /** @var CompilerExtension */
    private $extension;

    /** @var array|object */
    private $config;

    /** @var string */
    private $default = 'array';

    private function __construct(CompilerExtension $extension)
    {
        $this->extension = $extension;
    }

    public static function of(CompilerExtension $extension): self
    {
        return new self($extension);
    }

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function setConfig($config): self
    {
        $this->config = $config;

        return $this;
    }

    public function withDefault(string $driver): self
    {
        if (!isset(self::DRIVERS[$driver])) {
            throw new InvalidArgumentException(\sprintf('Unsupported default cache driver "%s"', $driver));
        }

        $this->default = $driver;

        return $this;
    }

    public function getDefinition(string $service): ServiceDefinition
    {
        $builder = $this->extension->getContainerBuilder();

        $def = $builder->addDefinition($service)
            ->setFactory(self::DRIVERS[$this->default])
        ;

        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
        if (isset($this->default)) {
            $pools = $this->config->pools;

            if (\method_exists($this, $method = 'create' . \ucfirst($this->default))) {
                $this->{$method}($def, $builder, $pools);
            }
        }

        return $def;
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createFilesystem(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $filesystem = $pools['filesystem'];
        $path       = $builder->parameters['path']['TEMP'] ?? $builder->parameters['tempDir'];

        $definition->setArguments([
            isset($builder->parameters['tempDir'])
                ? $path . '/cache/psr16'
                : $path . '/caches/biurad' . '.caching', $filesystem['extension'],
        ]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createMemory(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $filesystem = $pools['memory'];
        $path       = $builder->parameters['path']['TEMP'] ?? $builder->parameters['tempDir'];

        $definition->setArguments([
            isset($builder->parameters['tempDir'])
                ? $path . '/cache/psr16'
                : $path . '/caches/biurad' . '.caching', $filesystem['extension'],
        ]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createApcu(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
        if (!\extension_loaded('apcu')) {
            throw new CacheException(
                'Sorry, apcu driver no found. If you think this is an error!. Enable apcu and try again'
            );
        }
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     *
     * @deprecated
     */
    protected function createXcache(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        // This driver connection should be void.
        throw new CacheException(
            'Sorry, This driver has been deprecated, and will be removed in future development',
            \E_DEPRECATED
        );
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createRedis(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!\class_exists(Redis::class)) {
            throw new CacheException(
                'Sorry, redis driver not found. If you think this is an error!. Enable redis and try again'
            );
        }

        $redis         = $pools['redis'];
        [$host, $port] = \explode(':', $redis['connection']);

        $definition->addSetup('setRedis', [new Statement([Connection::class, 'createRedis'], [$host, $port])]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createMemcache(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!\class_exists(Memcache::class)) {
            throw new CacheException(
                'Sorry, memcache driver not found. If you think this is an error!. Enable memcache and try again'
            );
        }

        $memcache      = $pools['memcache'];
        [$host, $port] = \explode(':', $memcache['connection']);

        $definition->addSetup('setMemcache', [new Statement([Connection::class, 'createMemcache'], [$host, $port])]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createMemcached(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        if (!\class_exists(Memcached::class)) {
            throw new CacheException(
                'Sorry, memcached driver not found. If you think this is an error!. Enable memcached and try again'
            );
        }

        $memcached     = $pools['memcache'];
        [$host, $port] = \explode(':', $memcached['connection']);

        $definition->addSetup(
            'setMemcached',
            [new Statement([Connection::class, 'createMemcached'], [$host, $port, $memcached['options']])]
        );
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder  $builder
     * @param array             $pools
     */
    protected function createSqlite(ServiceDefinition $definition, ContainerBuilder $builder, array $pools): void
    {
        $sqlite = $pools['sqlite'];
        $path   = $builder->parameters['path']['TEMP'] ?? $builder->parameters['tempDir'];

        $filename = $sqlite['connection'];
        $table    = $sqlite['table'];

        $filePath = "{$path}/database/{$filename}";

        if (false !== \strpos($filename, $path)) {
            $filePath = $filename;

            if (false === \strpos($filePath, 'database')) {
                $filePath = "{$path}/database/" . \substr($filePath, \strlen($path) + 1);
            }
        }

        $definition->setArguments([new Statement([Connection::class, 'createSqlite'], [$filePath]), $table]);
    }
}
