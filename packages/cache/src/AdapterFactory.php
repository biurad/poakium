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

namespace BiuradPHP\Cache;

use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use Doctrine\Common\Cache as DoctrineCache;
use Memcache;
use Memcached;
use Redis;
use SQLite3;
use TypeError;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AdapterFactory
{
    /**
     * @param object|string $connection Connection or DSN
     *
     * @return Doctrine\Common\Cache\Cache
     */
    public static function createHandler($connection): DoctrineCache\Cache
    {
        if (!\is_string($connection) && !\is_object($connection)) {
            throw new TypeError(
                \sprintf(
                    'Argument 1 passed to %s() must be a string or a connection object, %s given.',
                    __METHOD__,
                    \gettype($connection)
                )
            );
        }

        switch (true) {
            case $connection instanceof DoctrineCache\Cache:
                return $connection;

            case $connection instanceof Redis:
                $adapter = new DoctrineCache\RedisCache();

                if (!$connection->isConnected()) {
                    throw new InvalidArgumentException('Did you forget to call the \'connect\' method');
                }
                $adapter->setRedis($connection);

                return $adapter;

            case $connection instanceof Memcache:
                $adapter = new DoctrineCache\MemcacheCache();
                $adapter->setMemcache($connection);

                return $adapter;

            case $connection instanceof Memcached:
                $adapter = new DoctrineCache\MemcacheCached();
                $adapter->setMemcached($connection);

                return $adapter;

            case 0 === \strpos($connection, 'array'):
                return new DoctrineCache\ArrayCache();

            case 0 === \strpos($connection, 'apcu'):
                return new DoctrineCache\ApcuCache();

            case 0 === \strpos($connection, 'wincache'):
                return new DoctrineCache\WinCacheCache();

            case 0 === \strpos($connection, 'zenddata'):
                return new DoctrineCache\ZendDataCache();

            case 0 === \strpos($connection, 'redis://'):
                $adapter = new DoctrineCache\RedisCache();

                [$host, $port] = \explode(':', \substr($connection, 8));
                ($redis = new Redis())->connect($host, $port);
                $adapter->setRedis($redis);

                return $adapter;

            case 0 === \strpos($connection, 'memcache://'):
                $adapter = new DoctrineCache\MemcacheCache();

                [$host, $port] = \explode(':', \substr($connection, 11));
                $adapter->setMemcache(\memcache_pconnect($host, $port));

                return $adapter;

            case 0 === \strpos($connection, 'memcached://'):
                $adapter = new DoctrineCache\MemcacheCached();

                [$host, $port] = \explode(':', \substr($connection, 12));
                ($memcached = new Memcached())->addServer($host, $port);
                $adapter->setMemcached($memcached);

                return $adapter;

            case 0 === \strpos($connection, 'file://'):
                $extension = '.cache.data';

                if (\strpos(':', $tempDir = \substr($connection, 7))) {
                    [$tempDir, $extension] = \explode(':', $tempDir);
                }

                return new DoctrineCache\FilesystemCache($tempDir, $extension);

            case 0 === \strpos($connection, 'memory://'):
                $extension = '.cache.php';

                if (\strpos(':', $tempDir = \substr($connection, 7))) {
                    [$tempDir, $extension] = \explode(':', $tempDir);
                }

                return new DoctrineCache\PhpFileCache($tempDir, $extension);

            case 0 === \strpos($connection, 'sqlite://'):
                [$table, $filename] = \explode(':', \substr($connection, 9));

                return new DoctrineCache\SQLite3Cache(new SQLite3($filename), $table);
        }

        throw new InvalidArgumentException(\sprintf('Unsupported Cache Adapter: %s.', $connection));
    }
}
