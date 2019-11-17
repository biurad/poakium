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

use Nette\SmartObject;
use InvalidArgumentException;
use PDO, Doctrine, BiuradPHP;
use BiuradPHP\Manager\PoolManager;
use BiuradPHP\Event\Interfaces\EventInterface;

class CacheResolver extends BiuradPHP\Cache\Cache
{
    use PoolManager, SmartObject;

    /** @var array|object */
    private $config = [];

    /** @var string */
    private $path;

    public function __construct($configs, string $path, ?EventInterface $event)
    {
        $this->path = $path;
        $this->config = $configs;

        return parent::__construct($this->getPoolAdapter(), $event);
    }

    /**
     * Get the necessary pools for caching.
     */
    private function getPools()
    {
        $config = $this->config;
        $pools = $config->pools;

        return [
            'void' => function () {
                return new Doctrine\Common\Cache\VoidCache();
            },
            
            'apcu' => function () {
                return new Doctrine\Common\Cache\ApcuCache();
            },

            'xcache' => function () {
                return new Doctrine\Common\Cache\XcacheCache();
            },

            'zenddata' => function () {
                return new Doctrine\Common\Cache\ZendDataCache();
            },

            'wincache' => function () {
                return new Doctrine\Common\Cache\WinCacheCache();
            },

            'filesystem' => function () use (&$pools) {
                $path = $pools['filesystem']['connection'];

                if (empty($path)) {
                    $path = $this->path . DIRECTORY_SEPARATOR . 'caches';
                }
                //final path.
                $path = $path . DIRECTORY_SEPARATOR . 'biurad.caching';

                return new Doctrine\Common\Cache\FilesystemCache($path, '.temp');
            },

            'memcached' => function () use (&$pools) {
                [$host, $port] = explode(':', $pools['memcached']['connection']);
                $options = $pools['memcached']['options'];

                $client = new \Memcached();
                $client->setOptions($options);
                $client->addServer($host, (int) $port);

                $cache = new Doctrine\Common\Cache\MemcachedCache();
                $cache->setMemcached($client);

                return $cache;
            },

            'mongodb' => function () use (&$pools) {
                $connection = $pools['mongodb']['connection'];
                $db = $pools['mongodb']['database'];
                $collection = $pools['mongodb']['collection'];

                $client = new \MongoDB\Driver\Manager("mongodb://{$connection}");
                $collection = new \MongoDB\Collection($client, $db, $collection);

                return new Doctrine\Common\Cache\MongoDBCache($collection);
            },

            'sqlite' => function () use (&$pools) {
                $db = $pools['sqlite']['database'];
                $table = $pools['sqlite']['table'];

                $client = new \SQLite3($db);

                return new Doctrine\Common\Cache\SQLite3Cache($client, $table);
            },

            'redis' => function () use (&$pools) {
                [$host, $port] = explode(':', $pools['redis']['connection']);
                $client = new \Redis();
                $client->connect($host, (int) $port);

                $cache = new Doctrine\Common\Cache\RedisCache();
                $cache->setRedis($client);

                return $cache;
            },

            'mysqli' => function () use (&$pools) {
                [$host, $port] = explode(':', $pools['mysqli']['connection']);
                $username = $pools['mysqli']['auth']['username'];
                $password = $pools['mysqli']['auth']['password'];
                $db = $pools['mysqli']['database'];
                $table = $pools['mysqli']['table'];
                $options = $pools['mysqli']['options'];

                return new BiuradPHP\Cache\Handlers\DatabaseCache(
                    new PDO("mysql:host=$host;dbname=$db;", $username, $password),
                    $table,
                    $options
                );
            },
        ];
    }

    /**
     * Get Cache Adapter
     *
     * @return Doctrine\Common\Cache\Cache|mixed
     */
    public function getPoolAdapter()
    {
        $config = $this->config;
        $pools = array_merge($this->pools, $this->getPools($config));
        $adapter = isset($this->driver) ? $this->driver : $config->driver;

        if (($config && $adapter && true !== $config->enabled)) {
            return $pools['void']();
        }

        if (($config && $adapter && isset($config->enabled)) && false !== $config->enabled) {
            if (!isset($pools[$adapter])) {
                throw new InvalidArgumentException("cache requires configuration for [$adapter] adapter");
            }

            return $pools[$adapter]();
        }

        return new Doctrine\Common\Cache\ArrayCache();
    }
}
