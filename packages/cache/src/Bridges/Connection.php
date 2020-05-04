<?php /** @noinspection PhpComposerExtensionStubsInspection */

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

use Redis, Memcache, Memcached, SQLite3;

use function is_array;

class Connection
{
    /**
     * Create a Sqlite Connection
     *
     * @param string $filename
     *
     * @return SQLite3
     */
    public static function createSqlite($filename): SQLite3
    {
        return new SQLite3($filename);
    }

    /**
     * Create a Redis Connection
     *
     * @param string $host
     * @param int $port
     *
     * @return Redis
     */
    public static function createRedis($host, $port): Redis
    {
        $client = new Redis();
        $client->connect($host, (int) $port);

        return $client;
    }

    /**
     * Create Memcache Connection
     *
     * @param string $host
     * @param int $port
     *
     * @return Memcache
     */
    public static function createMemcache($host, $port): Memcache
    {
        $client = new Memcache();
        //$client->setOptions($options);
        $client->addServer($host, (int) $port);

        return $client;
    }

    /**
     * Create Memcache Connection
     *
     * @param string $host
     * @param int $port
     * @param array|null $options
     *
     * @return Memcached
     */
    public static function createMemcached($host, $port, $options): Memcached
    {
        $client = new Memcached();
        $client->addServer($host, (int) $port);

        if (null !== $options && is_array($options) && !empty($options)) {
            $client->setOptions($options);
        }

        return $client;
    }
}
