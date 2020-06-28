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

use BadMethodCallException;
use Closure;
use Exception;
use Generator;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Traversable;

class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var CacheInterface
     */
    protected $pool;

    /**
     * @var null|int The maximum length to enforce for identifiers or null when no limit applies
     */
    protected $maxIdLength;

    /**
     * @var Closure needs to be set by class, signature is function(string <key>, mixed <value>, bool <isHit>)
     */
    private $createCacheItem;

    /**
     * @var Closure needs to be set by class, signature is function(array <deferred>, array <&expiredIds>)
     */
    private $mergeByLifetime;

    private $deferred = [];

    private $ids = [];

    private $miss;

    /**
     * Cache Constructor.
     *
     * @param CacheInterface $psr16
     */
    public function __construct(CacheInterface $psr16)
    {
        $this->pool = $psr16;
        $this->miss = new stdClass();

        $this->createCacheItem = Closure::bind(
            static function ($key, $value, $isHit) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $v = $value;
                $item->isHit = $isHit;
                $item->defaultLifetime = 0;
                // Detect wrapped values that encode for their expiry and creation duration
                // For compactness, these values are packed in the key of an array using
                // magic numbers in the form 9D-..-..-..-..-00-..-..-..-5F
                if (
                    \is_array($v) &&
                    1 === \count($v) &&
                    10 === \strlen($k = (string) \key($v)) &&
                    "\x9D" === $k[0] &&
                    "\0" === $k[5] &&
                    "\x5F" === $k[9]
                ) {
                    $item->value = $v[$k];
                }

                return $item;
            },
            null,
            CacheItem::class
        );
        $getId                 = Closure::fromCallable([$this, 'getId']);
        $this->mergeByLifetime = Closure::bind(
            static function ($deferred, &$expiredIds) use ($getId) {
                $byLifetime = [];
                $now = \microtime(true);
                $expiredIds = [];

                foreach ($deferred as $key => $item) {
                    $key = (string) $key;

                    if (null === $item->expiry) {
                        $ttl = 0 < $item->defaultLifetime ? $item->defaultLifetime : 0;
                    } elseif (0 >= $ttl = (int) (0.1 + $item->expiry - $now)) {
                        $expiredIds[] = $getId($key);

                        continue;
                    }

                    // For compactness, expiry and creation duration are packed in the key of an array,
                    // using magic numbers as separators
                    $byLifetime[$ttl][$getId($key)] = $item->value;
                }

                return $byLifetime;
            },
            null,
            CacheItem::class
        );
    }

    public function __destruct()
    {
        if ($this->deferred) {
            $this->commit();
        }
    }

    public function __sleep(): void
    {
        throw new BadMethodCallException('Cannot serialize ' . __CLASS__);
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if ($this->deferred) {
            $this->commit();
        }
        $id = $this->getId($key);

        $f     = $this->createCacheItem;
        $isHit = false;
        $value = null;

        try {
            foreach ($this->doFetch([$id]) as $value) {
                $isHit = true;
            }

            return $f($key, $value, $isHit);
        } catch (Exception $e) {
        }

        return $f($key, null, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        if ($this->deferred) {
            $this->commit();
        }
        $ids = [];

        foreach ($keys as $key) {
            $ids[] = $this->getId($key);
        }

        $items = $this->doFetch($ids);
        $ids   = \array_combine($ids, $keys);

        return $this->generateItems($items, $ids);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        $id = $this->getId($key);

        if (isset($this->deferred[$key])) {
            $this->commit();
        }

        return $this->doHave($id);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->deferred = [];

        return $this->doClear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $ids = [];

        foreach ($keys as $key) {
            $ids[$key] = $this->getId($key);
            unset($this->deferred[$key]);
        }

        return $this->doDelete($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        $this->saveDeferred($item);

        return $this->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $ok             = true;
        $byLifetime     = $this->mergeByLifetime;
        $byLifetime     = $byLifetime($this->deferred, $expiredIds);
        $this->deferred = [];

        if ($expiredIds) {
            $this->doDelete($expiredIds);
        }

        foreach ($byLifetime as $lifetime => $values) {
            if ($this->doSave($values, $lifetime)) {
                continue;
            }

            $ok = false;
        }

        return $ok;
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|Traversable The corresponding values found in the cache
     */
    protected function doFetch(array $ids)
    {
        $fetched = $this->pool->getMultiple($ids, $this->miss);

        if ($fetched instanceof Generator) {
            $fetched = $fetched->getReturn();
        }

        foreach ($fetched as $key => $value) {
            if ($this->miss !== $value) {
                yield $key => $value;
            }
        }
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    protected function doHave(string $id)
    {
        return $this->pool->has($id);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear()
    {
        return $this->pool->clear();
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete(array $ids)
    {
        return $this->pool->deleteMultiple($ids);
    }

    /**
     * Persists several cache items immediately.
     *
     * @param array $values   The values to cache, indexed by their cache identifier
     * @param int   $lifetime The lifetime of the cached values, 0 for persisting until manual cleaning
     *
     * @return bool a boolean stating if caching succeeded or not
     */
    protected function doSave(array $values, int $lifetime)
    {
        return $this->pool->setMultiple($values, 0 === $lifetime ? null : $lifetime);
    }

    private function generateItems(iterable $items, array &$keys): iterable
    {
        $f = $this->createCacheItem;

        foreach ($items as $id => $value) {
            if (!isset($keys[$id])) {
                $id = \key($keys);
            }
            $key = $keys[$id];
            unset($keys[$id]);

            yield $key => $f($key, $value, true);
        }

        foreach ($keys as $key) {
            yield $key => $f($key, null, false);
        }
    }

    private function getId($key)
    {
        if (\is_string($key) && isset($this->ids[$key])) {
            return $this->ids[$key];
        }
        CacheItem::validateKey($key);
        $this->ids[$key] = $key;

        return $key;
    }
}
