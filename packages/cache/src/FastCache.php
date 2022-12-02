<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

use Biurad\Cache\Exceptions\CacheException;
use Biurad\Cache\Exceptions\InvalidArgumentException;
use Cache\Adapter\Common\HasExpirationTimestampInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;

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

    /** @var CacheInterface|CacheItemPoolInterface */
    private $storage;

    /** @var string */
    private $namespace;

    /** @var array<string,mixed> */
    private $computing = [];

    /** @var string */
    private $cacheItemClass = CacheItem::class;

    /**
     * @param CacheInterface|CacheItemPoolInterface $storage
     */
    final public function __construct($storage, string $namespace = self::NAMESPACE)
    {
        if (!($storage instanceof CacheInterface || $storage instanceof CacheItemPoolInterface)) {
            throw new CacheException('$storage can only implements PSR-6 or PSR-16 cache interface.');
        }

        $this->storage = $storage;
        $this->namespace = $namespace;
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

    /**
     * @return CacheInterface|CacheItemPoolInterface
     */
    public function getStorage()
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
        return new static($this->storage, $this->namespace . $namespace);
    }

    /**
     * Reads the specified item from the cache or generate it.
     *
     * @return mixed
     */
    public function load(string $key, callable $fallback = null, ?float $beta = null)
    {
        $data = $this->doFetch($this->namespace . $key);

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
     * @param array<string,mixed> $keys
     *
     * @return array<int|string,mixed>
     */
    public function bulkLoad(array $keys, callable $fallback = null, ?float $beta = null): array
    {
        if (empty($keys)) {
            return [];
        }

        $result = [];

        foreach ($keys as $key) {
            if (!\is_string($key)) {
                throw new \InvalidArgumentException('Only string keys are allowed in bulkLoad().');
            }

            $result[$key] = $this->load(
                \md5($key), // encode key
                static function (CacheItemInterface $item) use ($key, $fallback) {
                    return $fallback($key, $item);
                },
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
    public function save(string $key, callable $callback, ?float $beta = null)
    {
        $key = $this->namespace . $key;

        if (0 > $beta = $beta ?? 1.0) {
            throw new InvalidArgumentException(
                \sprintf('Argument "$beta" provided to "%s::save()" must be a positive number, %f given.', __CLASS__, $beta)
            );
        }

        return $this->doSave($key, $callback, $beta);
    }

    /**
     * Remove an item from the cache.
     */
    public function delete(string $key): bool
    {
        return $this->doDelete($key);
    }

    /**
     * Caches results of function/method calls.
     *
     * @return mixed
     */
    public function call(callable $callback, ...$arguments)
    {
        $key = $arguments;

        if (\is_array($callback) && \is_object($callback[0])) {
            $key[0][0] = \get_class($callback[0]);
        }

        return $this->load(
            $this->generateKey($key),
            static function (CacheItemInterface $item) use ($callback, $key) {
                return $callback(...$key + [$item]);
            }
        );
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
        return function () use ($callback) {
            return $this->call($callback, ...\func_get_args());
        };
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
     * Generates internal cache key.
     *
     * @param mixed $key
     */
    private function generateKey($key): string
    {
        if (\is_object($key)) {
            $key = \spl_object_id($key);
        } elseif (\is_array($key)) {
            $key = \md5(\implode('', $key));
        }

        return $this->namespace . (string) $key;
    }

    /**
     * Save cache item.
     *
     * @return mixed The corresponding values found in the cache
     */
    private function doSave(string $key, callable $callback, ?float $beta)
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

        $result = $this->doCreate(new $this->cacheItemClass(), $callback, $expiry);

        if ($result instanceof CacheItemInterface) {
            $result = $result->get();
        }

        $storage->set($key, $result, $expiry);

        return $result;
    }

    /**
     * @param int $expiry
     *
     * @return mixed|CacheItemInterface
     */
    private function doCreate(CacheItemInterface $item, callable $callback, int &$expiry = null)
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
    private function doFetch($ids)
    {
        $fetchMethod = $this->storage instanceof CacheItemPoolInterface
            ? 'getItem' . (\is_array($ids) ? 's' : null)
            : 'get' . (!\is_array($ids) ? 'Multiple' : null);

        return $this->storage->{$fetchMethod}($ids);
    }

    /**
     * Remove an item from cache.
     *
     * @param string $id An identifier that should be removed from cache
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    private function doDelete(string $id)
    {
        if ($this->storage instanceof CacheItemPoolInterface) {
            $deleteItem = 'Item';
        }

        return $this->storage->{'delete' . $deleteItem ?? null}($id);
    }
}
