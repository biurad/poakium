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

use Memcache;
use Memcached;
use Redis;
use SQLite3;

class Connection
{
    /**
     * Create a Sqlite Connection.
     *
     * @param string $filename
     */
    public static function createSqlite($filename): SQLite3
    {
        return new SQLite3($filename);
    }

    /**
     * Create a Redis Connection.
     *
     * @param string $host
     * @param int    $port
     */
    public static function createRedis($host, $port): Redis
    {
        $client = new Redis();
        $client->connect($host, (int) $port);

        return $client;
    }

    /**
     * Create Memcache Connection.
     *
     * @param string $host
     * @param int    $port
     */
    public static function createMemcache($host, $port): Memcache
    {
        $client = new Memcache();
        //$client->setOptions($options);
        $client->addServer($host, (int) $port);

        return $client;
    }

    /**
     * Create Memcache Connection.
     *
     * @param string     $host
     * @param int        $port
     * @param null|array $options
     */
    public static function createMemcached($host, $port, $options): Memcached
    {
        $client = new Memcached();
        $client->addServer($host, (int) $port);

        if (null !== $options && \is_array($options) && !empty($options)) {
            $client->setOptions($options);
        }

        return $client;
    }
}
