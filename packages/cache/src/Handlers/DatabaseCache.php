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

namespace BiuradPHP\Cache\Handlers;

use PDO;
use Exception;
use Doctrine\Common\Cache\CacheProvider;

class DatabaseCache extends CacheProvider
{
    /**
     * The database connection instance.
     *
     * @var PDO
     */
    protected $connection;

    /**
     * The name of the cache table.
     *
     * @var string
     */
    protected $table;

    /**
     * The data passed into the database query.
     *
     * @var array
     */
    protected $options = [];

    private $expire;

    /**
     * Create a new database store.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @param  string  $prefix
     * @return void
     */
    public function __construct(PDO $connection, $table, $options)
    {
        $this->table = $table;
        $this->connection = $connection;
        $this->options = array_merge([
            'cache_id' => 'id',
            'cache_data' => 'data',
            'cache_time' => 'expire',
        ], $options);

        $this->ensureTableExists();
    }

    private function ensureTableExists(): bool
    {
        [$cacheId, $cacheData, $cacheTime] = $this->getFields();

        $tables = $this->connection->prepare(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    %s VARCHAR(255) DEFAULT NULL,
                    %s BLOB NOT NULL,
                    %s BIGINT(20) DEFAULT NULL,
                    UNIQUE KEY `id` (`id`)
                )',
                $this->table,
                $cacheId,
                $cacheData,
                $cacheTime
            )
        );

        return $tables->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $item = $this->findById($id);
        $cacheData = $this->options['cache_data'];

        if (!$item) {
            return false;
        }

        return unserialize($item[$cacheData]);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->findById($id, false) !== null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $value = serialize($data);
        [$cacheId, $cacheData, $cacheTime] = $this->getFields();
        $this->expire = $expiration = $lifeTime > 0 ? time() + $lifeTime : null;

        try {
            $inserted = $this->connection->prepare(sprintf(
                'INSERT INTO %s (%s) VALUES (:id, :data, :expire)',
                $this->table,
                implode(', ', $this->getFields())
            ));

            $inserted->bindValue(':id', $id);
            $inserted->bindValue(':data', serialize($data));
            $inserted->bindValue(':expire', $expiration);

            return $inserted->execute();
        } catch (Exception $e) {
            $result = $this->connection->prepare(sprintf(
                'UPDATE %s
                SET
                    %s = %s
                    %s = %s
                WHERE %s = %s',
                $this->table,
                $cacheData,
                $value,
                $cacheTime,
                $expiration,
                $cacheId,
                $id
            ));

            return $result->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $table = $this->table;
        [$cacheId] = $this->getFields();

        $deleted = $this->connection->prepare("
            DELETE FROM $table WHERE $cacheId = '$id'
        ");

        return $deleted->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $table = $this->table;
        $cacheTime = $this->options['cache_time'];
        $expired = time() - $this->expire;

        $flushed = $this->connection->prepare("
            DELETE FROM $table WHERE $cacheTime = '$cacheTime' <= $expired
        ");

        return $flushed->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        // no-op.
    }

    /**
     * Find a single row by ID.
     *
     * @param mixed $id
     *
     * @return array|null
     */
    private function findById($id, bool $includeData = true): ?array
    {
        [$idField] = $fields = $this->getFields();

        if (!$includeData) {
            $key = array_search($this->options['cache_data'], $fields);
            unset($fields[$key]);
        }

        $statement = $this->connection->prepare(sprintf(
            "SELECT %s FROM `%s` WHERE %s = ':id' LIMIT 1",
            implode(', ', $fields),
            $this->table,
            $idField
        ));

        $statement->execute([':id' => $id]);

        $item = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($item !== true) {
            return null;
        }

        if ($this->isExpired($item)) {
            $this->doDelete($id);

            return null;
        }

        return $item;
    }

    /**
     * Gets an array of the fields in our table.
     *
     * @return array
     */
    private function getFields(): array
    {
        return [$this->options['cache_id'], $this->options['cache_data'], $this->options['cache_time']];
    }

    /**
     * Check if the item is expired.
     *
     * @param array $item
     */
    private function isExpired(array $item): bool
    {
        return isset($item[$this->options['cache_time']]) &&
            $item[$this->options['cache_time']] !== null &&
            $item[$this->options['cache_time']] < time();
    }
}
