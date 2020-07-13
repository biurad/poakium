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
     * @return DoctrineCache\Cache
     */
    public static function createHandler($connection): DoctrineCache\Cache
    {
        if (!(\is_string($connection) || \is_object($connection))) {
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
                $adapter = new DoctrineCache\MemcachedCache();
                $adapter->setMemcached($connection);

                return $adapter;

            case self::isPrefixedAdapter($connection, 'array'):
                return new DoctrineCache\ArrayCache();

            case self::isPrefixedAdapter($connection, 'apcu'):
                return new DoctrineCache\ApcuCache();

            case self::isPrefixedAdapter($connection, 'wincache'):
                return new DoctrineCache\WinCacheCache();

            case self::isPrefixedAdapter($connection, 'zenddata'):
                return new DoctrineCache\ZendDataCache();

            case self::isPrefixedAdapter($connection, 'redis://'):
                $adapter = new DoctrineCache\RedisCache();

                [$host, $port] = \explode(':', \substr((string) $connection, 8));
                ($redis = new Redis())->connect($host, (int) $port);
                $adapter->setRedis($redis);

                return $adapter;

            case self::isPrefixedAdapter($connection, 'memcache://'):
                $adapter = new DoctrineCache\MemcacheCache();

                [$host, $port] = self::getPrefixedAdapter($connection, 11);
                $adapter->setMemcache(\memcache_pconnect($host, (int) $port));

                return $adapter;

            case self::isPrefixedAdapter($connection, 'memcached://'):
                $adapter = new DoctrineCache\MemcachedCache();

                [$host, $port] = self::getPrefixedAdapter($connection, 12);
                ($memcached = new Memcached())->addServer($host, (int) $port);
                $adapter->setMemcached($memcached);

                return $adapter;

            case self::isPrefixedAdapter($connection, 'file://'):
                [$tempDir, $extension] = self::getPrefixedAdapter($connection, 7, false);

                return new DoctrineCache\FilesystemCache($tempDir, $extension . 'data');

            case self::isPrefixedAdapter($connection, 'memory://'):
                [$tempDir, $extension] = self::getPrefixedAdapter($connection, 9, false);

                return new DoctrineCache\PhpFileCache($tempDir, $extension . 'php');

            case self::isPrefixedAdapter($connection, 'sqlite://'):
                [$table, $filename] = self::getPrefixedAdapter($connection, 9);

                return new DoctrineCache\SQLite3Cache(new SQLite3($filename), $table);
        }

        throw new InvalidArgumentException(
            \sprintf('Unsupported Cache Adapter: %s.', \is_object($connection) ? \get_class($connection) : $connection)
        );
    }

    /**
     * @param mixed $connection
     * @param bool  $host
     *
     * @return false|string[]
     */
    private static function getPrefixedAdapter($connection, int $limit, bool $host = true)
    {
        if (true === $host) {
            return \explode(':', \substr((string) $connection, $limit));
        }

        if (\strpos(':', $tempDir = \substr((string) $connection, $limit))) {
            return \explode(':', $tempDir);
        }

        return [$tempDir, '.cache.'];
    }

    /**
     * @param mixed  $connection
     * @param string $name
     *
     * @return bool
     */
    private static function isPrefixedAdapter($connection, string $name): bool
    {
        return \is_string($connection) && 0 === \strpos($connection, $name);
    }
}
