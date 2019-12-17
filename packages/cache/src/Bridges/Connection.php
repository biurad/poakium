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

class Connection
{
    public static function createSqlite($filename): \SQLite3
    {
        return new \SQLite3($filename);
    }

    public static function createRedis($host, $port): \Redis
    {
        $client = new \Redis();
        $client->connect($host, (int) $port);

        return $client;
    }

    public static function createMemcached($host, $port, $options): \Memcached
    {
        $client = new \Memcached();
        $client->setOptions($options);
        $client->addServer($host, (int) $port);

        return $client;
    }

    public static function createMysqli(
        $host, $port, $db,
        $username, $password
    ): \PDO {
        return new \PDO("mysql:host=$host;port=$port;dbname=$db;", $username, $password);
    }
}
