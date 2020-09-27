<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Http\Sessions;

use BiuradPHP\Http\Interfaces\QueueingCookieInterface;
use InvalidArgumentException;
use PDO;
use Psr\SimpleCache\CacheInterface;
use Spiral\Database\DatabaseInterface;
use TypeError;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class HandlerFactory
{
    /** @var null|CacheInterface */
    private $cache;

    /** @var null|QueueingCookieInterface */
    private $cookie;

    /** @var null|DatabaseInterface */
    private $database;

    /** @var null|int|string */
    private $minutes;

    /**
     * @param null|CacheInterface          $cache
     * @param null|QueueingCookieInterface $cookie
     * @param null|DatabaseInterface       $database
     * @param null|int|string              $minutes
     */
    public function __construct(
        ?CacheInterface $cache = null,
        ?QueueingCookieInterface $cookie = null,
        ?DatabaseInterface $database = null,
        $minutes = null
    ) {
        $this->cache    = $cache;
        $this->cookie   = $cookie;
        $this->database = $database;
        $this->minutes  = $minutes;
    }

    /**
     * @param PDO|string $connection Connection or DSN
     *
     * @return Handlers\AbstractSessionHandler
     */
    public function createHandler($connection): Handlers\AbstractSessionHandler
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
            case $connection instanceof PDO:
                return new Handlers\PdoSessionHandler($connection);

            case !\is_string($connection):
                throw new InvalidArgumentException(\sprintf('Unsupported Connection: %s.', \get_class($connection)));

            case 0 === \strpos($connection, 'array'):
                return new Handlers\NullSessionHandler();

            case 0 === \strpos($connection, 'cookie'):
                return new Handlers\CookieSessionHandler($this->cookie, $this->minutes);

            case 0 === \strpos($connection, 'file://'):
                return new Handlers\StrictSessionHandler(
                    new Handlers\NativeFileSessionHandler(\substr($connection, 7))
                );

            case 0 === \strpos($connection, 'cache-based'):
                return new Handlers\CacheSessionHandler($this->cache, $this->minutes);

            case 0 === \strpos($connection, 'database'):
            case 0 === \strpos($connection, 'cycle'):
                if (!$this->database instanceof DatabaseInterface) {
                    throw new InvalidArgumentException(
                        \sprintf(
                            'Unsupported DSN "%s". Try running "composer require spiral/database".',
                            $connection
                        )
                    );
                }
                $connection = $this->database->getDriver()->getPDO();
                // no break;

            case 0 === \strpos($connection, 'mssql://'):
            case 0 === \strpos($connection, 'mysql://'):
            case 0 === \strpos($connection, 'mysql2://'):
            case 0 === \strpos($connection, 'pgsql://'):
            case 0 === \strpos($connection, 'postgres://'):
            case 0 === \strpos($connection, 'postgresql://'):
            case 0 === \strpos($connection, 'sqlsrv://'):
            case 0 === \strpos($connection, 'sqlite://'):
            case 0 === \strpos($connection, 'sqlite3://'):
                return new Handlers\PdoSessionHandler($connection);
        }

        throw new InvalidArgumentException(\sprintf('Unsupported Connection: %s.', $connection));
    }
}
