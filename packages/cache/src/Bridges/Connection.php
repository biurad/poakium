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

use BiuradPHP\Database\Database;
use BiuradPHP\Database\DatabaseManager;

class Connection
{
    /**
     * Create a Sqlite Connection
     *
     * @param string $filename
     *
     * @return \SQLite3
     */
    public static function createSqlite($filename): \SQLite3
    {
        return new \SQLite3($filename);
    }

    /**
     * Create a Redis Connection
     *
     * @param string $host
     * @param int $port
     *
     * @return \Redis
     */
    public static function createRedis($host, $port): \Redis
    {
        $client = new \Redis();
        $client->connect($host, (int) $port);

        return $client;
    }

    /**
     * Create Memcache Connection
     *
     * @param string $host
     * @param int $port
     *
     * @return \Memcache
     */
    public static function createMemcache($host, $port): \Memcache
    {
        $client = new \Memcache();
        //$client->setOptions($options);
        $client->addServer($host, (int) $port);

        return $client;
    }

    /**
     * Create a Mysqli Connection using a database Driver.
     *
     * @param string $database
     * @param \BiuradPHP\Database\DatabaseManager $manager
     *
     * @return \BiuradPHP\Database\Interfaces\DatabaseInterface
     */
    public static function createMysqli(string $database, DatabaseManager $manager): Database {
        return $manager->database($database);
    }
}
