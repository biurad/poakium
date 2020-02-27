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

use Doctrine\Common\Cache\CacheProvider;
use BiuradPHP\Database\Interfaces\DatabaseInterface;

class DatabaseCache extends CacheProvider
{
    /**
     * The database connection instance.
     *
     * @var DatabaseInterface
     */
    protected $connection;

    /**
     * The name of the cache table.
     *
     * @var string
     */
    protected $table, $expire;

    /**
     * The data passed into the database query.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new database store.
     *
     * @param  DatabaseInterface  $connection
     * @param  string  $table
     * @param  string  $prefix
     * @return void
     */
    public function __construct(DatabaseInterface $connection, $table, $options)
    {
        $this->table = $table;
        $this->connection = $connection;
        $this->options = array_merge([
            'cache_id' => 'id',
            'cache_data' => 'data',
            'cache_time' => 'expire',
        ], $options);

        $this->ensureTableExists();
        $this->expire = time();
    }

    private function ensureTableExists(): bool
    {
        [$cacheId, $cacheData, $cacheTime] = $this->getFields();
        $table = $this->connection->table($this->table);

        if ($table->exists()) {
            return true;
        }

        // create or update table schema
        $schema = $table->getSchema();
        $schema->string($cacheId);
        $schema->index([$cacheId]);
        $schema->binary($cacheData);
        $schema->integer($cacheTime)->isNullable();
        $schema->save();

        return true;
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
        [$cacheId, $cacheData, $cacheTime] = $this->getFields();
        $expiration = $lifeTime > 0 ? $lifeTime : null;

        if (DatabaseInterface::SQLITE === $this->connection->getDriver()->getType()) {
            return $this->connection->execute(sprintf(
                'INSERT OR REPLACE INTO %s (%s) VALUES (:id, :data, :expire)',
                $this->table,
                implode(',', $this->getFields())
            ), [
                'id' => $id,
                'data' => serialize($data),
                'expire' => $expiration
            ]);
        }

        $updated = $this->connection->update(
            $this->table, [
                $cacheId => $id,
                $cacheData => serialize($data),
                $cacheTime => $expiration
            ], [$cacheId => $id]
        )->run();

        if (0 === $updated) {
            return $this->connection->table($this->table)
                ->insertOne([
                    $cacheId => $id,
                    $cacheData => serialize($data),
                    $cacheTime => $expiration
                ])
            ;
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        [$cacheId] = $this->getFields();

        $table = $this->connection->table($this->table);
        $table->delete([$cacheId => $id])->run();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $table = $this->connection->table($this->table);
        $table->delete()->run();

        return true;
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
        [$idField, $cacheData, $cacheTime] = $fields = $this->getFields();

        if (! $includeData) {
            $key = array_search($cacheData, $fields);
            unset($fields[$key]);
        }

        $table = $this->connection->table($this->table);

        $cache = $table->select([$idField, $cacheData, $cacheTime])
            ->where($idField, '=', $id)->fetchAll();

        // If we have a cache record we will check the expiration time against current
        // time on the system and see if the record has expired. If it has, we will
        // remove the records from the database table so it isn't returned again.
        $cache = empty($cache) ? null : $cache[0];
        if (is_null($cache)) {
            return null;
        }

        // If this cache expiration date is past the current time, we will remove this
        // item from the cache. Then we will return a null value since the cache is
        // expired. We will use "Carbon" to make this comparison with the column.
        if ($this->expire < time() - $cache[$cacheTime]) {
            $this->doDelete($id);

            return null;
        }

        return $cache;
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
}
