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

namespace Biurad\Cache;

use Biurad\Cache\Exceptions\InvalidArgumentException;
use Doctrine\Common\Cache\Cache as DoctrineCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Cache\MultiOperationCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    /** @var DoctrineCache */
    private $cache;

    /**
     * @var \Closure needs to be set by class, signature is function(string <key>, mixed <value>, bool <isHit>)
     */
    private $createCacheItem;

    /**
     * @var \Closure needs to be set by class, signature is function(array <deferred>, array <&expiredIds>)
     */
    private $mergeByLifetime;

    /** @var array<string,CacheItemInterface> */
    private $deferred = [];

    /** @var array<string,string> */
    private $ids = [];

    /**
     * Cache Constructor.
     *
     * @param DoctrineCache $doctrine
     */
    public function __construct(DoctrineCache $doctrine)
    {
        $this->cache = $doctrine;

        $this->createCacheItem();
        $this->mergeByLifetime(\Closure::fromCallable([$this, 'getId']));
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        if (!empty($this->deferred)) {
            $this->commit();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key): CacheItemInterface
    {
        if (!empty($this->deferred)) {
            $this->commit();
        }

        $id    = $this->getId($key);
        $isHit = $this->cache->contains($id);
        $value = $this->cache->fetch($id);

        return ($this->createCacheItem)($id, $value, $isHit);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Clear the deferred items
        $this->deferred = [];

        if ($this->cache instanceof FlushableCache) {
            return $this->cache->flushAll();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key): bool
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $deleted = true;

        foreach ($keys as $key) {
            $key = $this->getId($key);

            // Delete form deferred
            unset($this->deferred[$key]);

            // We have to commit here to be able to remove deferred hierarchy items
            $this->commit();

            if (!$this->cache->delete($key)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->saveDeferred($item);

        return $this->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $ok             = true;
        $byLifetime     = $this->mergeByLifetime;
        $expiredIds     = [];
        $byLifetime     = $byLifetime($this->deferred, $expiredIds);

        \assert($this->cache instanceof MultiOperationCache);

        if (!empty($expiredIds)) {
            $this->cache->deleteMultiple($expiredIds);
        }

        foreach ($byLifetime as $lifetime => $values) {
            if ($this->cache->saveMultiple($values, $lifetime)) {
                continue;
            }

            $ok = false;
        }
        $this->deferred = [];

        return $ok;
    }

    /**
     * @param mixed $key
     *
     * @return string
     */
    private function getId($key): string
    {
        if (!\is_string($key)) {
            throw new InvalidArgumentException(
                \sprintf('Cache key must be string, "%s" given', \gettype($key))
            );
        }

        $key = CacheItem::validateKey($key);

        return $this->ids[$key] ?? $this->ids[$key] = $key;
    }

    private function createCacheItem(): void
    {
        $this->createCacheItem = \Closure::bind(
            static function (string $key, $value, bool $isHit): CacheItemInterface {
                $item = new CacheItem();
                $item->key   = $key;
                $item->value = $v = $isHit ? $value : null;
                $item->isHit = $isHit;
                $item->defaultLifetime = 0;

                // Detect wrapped values that encode for their expiry and creation duration
                // For compactness, these values are packed in the key of an array using
                // magic numbers in the form 9D-..-..-..-..-00-..-..-..-5F
                // @codeCoverageIgnoreStart
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
                // @codeCoverageIgnoreEnd

                return $item;
            },
            null,
            CacheItem::class
        );
    }

    private function mergeByLifetime(callable $getId): void
    {
        $this->mergeByLifetime = \Closure::bind(
            static function ($deferred, &$expiredIds) use ($getId): array {
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
}
