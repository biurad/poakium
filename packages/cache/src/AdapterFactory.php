<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\Cache;

use Biurad\Cache\Exceptions\CacheException;
use Doctrine\Common\Cache as DoctrineCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\DoctrineProvider as SymfonyDoctrineProvider;

/**
 * Create a doctrine cache wrapped with PSR-6.
 *
 * @deprecated Deprecated without replacement since doctrine/cache version 1.11.
 *
 * @codeCoverageIgnore
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AdapterFactory
{
    /**
     * @param object|string $connection Connection or DSN
     */
    public static function createHandler($connection): CacheItemPoolInterface
    {
        static $adapter;

        switch (true) {
            case $connection instanceof DoctrineCache\Cache || $connection instanceof SymfonyDoctrineProvider:
                return CacheAdapter::wrap($connection);

            case \is_string($connection):
                if (\in_array($connection, ['array', 'apcu', 'win-cache', 'zend-data'], true)) {
                    $adapter = \sprintf('Doctrine\Common\Cache\%sCache', \ucwords($connection, '-'));

                    return CacheAdapter::wrap(new $adapter());
                }

                $connection = self::getPrefixedAdapter($connection, $adapter);

                if ($connection instanceof DoctrineCache\Cache) {
                    return CacheAdapter::wrap($connection);
                }

                // no break

            case $connection instanceof \Redis:
                $adapter = new DoctrineCache\RedisCache();

                $adapter->setRedis($connection);

                break;

            case $connection instanceof \Memcache:
                $adapter = new DoctrineCache\MemcacheCache();
                $adapter->setMemcache($connection);

                break;

            case $connection instanceof \Memcached:
                $adapter = new DoctrineCache\MemcachedCache();
                $adapter->setMemcached($connection);

                break;
        }

        if ($adapter instanceof DoctrineCache\Cache) {
            return CacheAdapter::wrap($adapter);
        }

        throw new CacheException(
            \sprintf('Unsupported Cache Adapter: %s.', \is_object($connection) ? \get_class($connection) : $connection)
        );
    }

    /**
     * @param mixed $adapter
     *
     * @return \Redis|\Memcache|\Memcached|DoctrineCache|null
     */
    private static function getPrefixedAdapter(string $connection, $adapter)
    {
        $connection = \parse_url($connection) ?: \explode('://', $connection, 2) ?: [];

        // Extract parsed connection string.
        list($scheme, $host, $port) = [
            $connection['scheme'] ?? $connection[0] ?? null,
            $connection['host'] ?? $connection[1] ?? null,
            $connection['port'] ?? null,
        ];

        if (isset($scheme, $host)) {
            switch ($connectionServer = \ucfirst($scheme)) {
                case 'Redis':
                    ($adapter = new \Redis())->connect($host, $port ?? 6379);

                    break;

                case 'Memcache':
                case 'Memcache':
                    /** @var \Memcache|\Memcached */
                    ($adapter = new $connectionServer())->addServer($host, $port ?? 11211);

                    break;

                case 'Serialize':
                case 'Serialise':
                    $adapter = new DoctrineCache\PhpFileCache($host);

                    break;

                case 'Filesystem':
                case 'File':
                    $adapter = new DoctrineCache\FilesystemCache($host);
            }
        }

        return $adapter;
    }
}
