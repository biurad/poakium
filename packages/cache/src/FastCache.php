<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache;

use Biurad\Cache\Exceptions\CacheException;
use Biurad\Cache\Exceptions\InvalidArgumentException;
use Cache\Adapter\Common\HasExpirationTimestampInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * An advanced caching system using PSR-6 or PSR-16.
 *
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FastCache
{
    private const NAMESPACE = '';

    /** @var array<string,mixed> */
    private array $computing = [];

    private string $cacheItemClass = CacheItem::class;

    final public function __construct(
        private CacheInterface|CacheItemPoolInterface $storage,
        private string $namespace = self::NAMESPACE
    ) {
    }

    /**
     * Set a custom cache item class.
     */
    public function setCacheItem(string $cacheItemClass): void
    {
        if (\is_subclass_of($cacheItemClass, CacheItemInterface::class)) {
            $this->cacheItemClass = $cacheItemClass;
        }
    }

    public function getStorage(): CacheInterface|CacheItemPoolInterface
    {
        return $this->storage;
    }

    /**
     * Returns cache namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Returns new nested cache object.
     */
    public function derive(string $namespace): self
    {
        return new static($this->storage, $this->namespace.$namespace);
    }

    /**
     * Reads the specified item from the cache or generate it.
     */
    public function load(string $key, callable $fallback = null, float $beta = null): mixed
    {
        $data = $this->doFetch($this->namespace.$key);

        if ($data instanceof CacheItemInterface) {
            $data = $data->isHit() ? $data->get() : null;
        }

        if (null === $data && null !== $fallback) {
            return $this->save($key, $fallback, $beta);
        }

        return $data;
    }

    /**
     * Reads multiple items from the cache.
     *
     * @param array<int,string> $keys
     *
     * @return array<string,mixed>
     */
    public function bulkLoad(array $keys, callable $fallback = null, float $beta = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->load(
                $key,
                $fallback ? static fn (CacheItemInterface $item): CacheItemInterface => $fallback($key, $item) : null,
                $beta
            );
        }

        return $result;
    }

    /**
     * Writes an item into the cache.
     *
     * @return mixed value itself
     */
    public function save(string $key, callable $callback, float $beta = null): mixed
    {
        $key = $this->namespace.$key;

        if (0 > $beta = $beta ?? 1.0) {
            throw new InvalidArgumentException(\sprintf('Argument "$beta" provided to "%s::save()" must be a positive number, %f given.', __CLASS__, $beta));
        }

        return $this->doSave($key, $callback, $beta);
    }

    /**
     * Remove an item from the cache.
     */
    public function delete(string $key): bool
    {
        return $this->doDelete($this->namespace.$key);
    }

    /**
     * Caches results of function/method calls.
     *
     * @param mixed ...$arguments Cannot contain closures
     */
    public function call(callable $callback, ...$arguments): mixed
    {
        if ($callback instanceof \Closure) {
            return $callback(...$arguments); // Closure's can't be cached
        }

        if (\is_object($callback)) {
            $key = $callback::class.'::__invoke';
        } elseif (\is_array($callback)) {
            $key = \is_object($callback[0]) ? \get_class($callback[0]).$callback[1] : \implode('', $callback);
        } else {
            $key = $callback;
        }

        return $this->load(\md5($key.\serialize($arguments)), function (CacheItemInterface $item) use ($callback, $arguments) {
            $item->set($callback(...$arguments));

            return $item;
        });
    }

    /**
     * Alias of `call` method wrapped with a closure.
     *
     * @see {@call}
     *
     * @return callable so arguments can be passed into for final results
     */
    public function wrap(callable $callback /* ... arguments passed to $callback */): callable
    {
        return fn () => $this->call($callback, ...\func_get_args());
    }

    /**
     * Starts the output cache.
     */
    public function start(string $key): ?OutputHelper
    {
        $data = $this->load($key);

        if (null === $data) {
            return new OutputHelper($this, $key);
        }
        echo $data;

        return null;
    }

    /**
     * Save cache item.
     *
     * @return mixed The corresponding values found in the cache
     */
    private function doSave(string $key, callable $callback, ?float $beta): mixed
    {
        $storage = $this->storage;

        if ($storage instanceof CacheItemPoolInterface) {
            $item = $storage->getItem($key);

            if (!$item->isHit() || \INF === $beta) {
                $result = $this->doCreate($item, $callback, $expiry);

                if (!$result instanceof CacheItemInterface) {
                    $result = $item->set($result);
                }

                $storage->save($result);
            }

            return $item->get();
        }

        $item = \Closure::bind(
            static function (string $item) use ($key): CacheItemInterface {
                $item = new $item();
                $item->key = $key;

                return $item;
            },
            null,
            $this->cacheItemClass
        );
        $result = $this->doCreate($item($this->cacheItemClass), $callback, $expiry);

        if ($result instanceof CacheItemInterface) {
            $result = $result->get();
        }

        $storage->set($key, $result, $expiry);

        return $result;
    }

    private function doCreate(CacheItemInterface $item, callable $callback, int &$expiry = null): mixed
    {
        $key = $item->getKey();

        // don't wrap nor save recursive calls
        if (isset($this->computing[$key])) {
            throw new CacheException(\sprintf('Duplicated cache key found "%s", causing a circular reference.', $key));
        }

        $this->computing[$key] = true;

        try {
            $item = $callback($item);

            // Find expiration time ...
            if ($item instanceof ExtendedCacheItemInterface) {
                $expiry = $item->getTtl();
            } elseif ($item instanceof CacheItemInterface) {
                if ($item instanceof HasExpirationTimestampInterface) {
                    $maxAge = $item->getExpirationTimestamp();
                } elseif (\method_exists($item, 'getExpiry')) {
                    $maxAge = $item->getExpiry();
                } else {
                    $maxAge = ((array) $item)["\0*\0expiry"] ?? null;
                }

                if (isset($maxAge)) {
                    $expiry = (int) (0.1 + $maxAge - \microtime(true));
                }
            }

            return $item;
        } catch (\Throwable $e) {
            $this->doDelete($key);

            throw $e;
        } finally {
            unset($this->computing[$key]);
        }
    }

    /**
     * Fetch cache item.
     *
     * @param string|string[] $ids The cache identifier to fetch
     *
     * @return mixed The corresponding values found in the cache
     */
    private function doFetch(string|array $ids): mixed
    {
        $fetchMethod = $this->storage instanceof CacheItemPoolInterface
            ? 'getItem'.(\is_array($ids) ? 's' : null)
            : 'get'.(\is_array($ids) ? 'Multiple' : null);

        return $this->storage->{$fetchMethod}($ids);
    }

    /**
     * Remove an item from cache.
     *
     * @param string $id An identifier that should be removed from cache
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    private function doDelete(string $id): bool
    {
        if ($this->storage instanceof CacheItemPoolInterface) {
            $deleteItem = 'Item';
        }

        return $this->storage->{'delete'.($deleteItem ?? null)}($id);
    }
}
