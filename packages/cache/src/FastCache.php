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

use BiuradPHP\Cache\Exceptions\CacheException;
use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use BiuradPHP\Cache\Interfaces\FastCacheInterface;
use Cache\Adapter\Common\CacheItem as PhpCacheItem;
use Cache\Adapter\Common\PhpCachePool;
use Closure;
use Generator;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Throwable;

/**
 * Implements the cache for a application.
 */
class FastCache implements FastCacheInterface
{
    /** @internal */
    public const NAMESPACE_SEPARATOR = "\x00";

    public const NAMESPACE = 'CACHE_KEY[%s]';

    /** @var CacheInterface|CacheItemPoolInterface */
    private $storage;

    /** @var string */
    private $namespace;

    /** @var array<string,mixed> */
    private $computing = [];

    /**
     * @param CacheInterface|CacheItemPoolInterface $storage
     * @param string                                $namespace
     */
    final public function __construct($storage, string $namespace = self::NAMESPACE)
    {
        if (
            !($storage instanceof CacheInterface || $storage instanceof CacheItemPoolInterface)
        ) {
            throw new CacheException('$storage can only implements psr-6 or psr-16 cache interface');
        }

        $this->storage   = $storage;
        $this->namespace = $namespace . self::NAMESPACE_SEPARATOR;
    }

    /**
     * {@inheritdoc}
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
        return (string) \substr(\sprintf($this->namespace, null), 0, -1);
    }

    /**
     * {@inheritdoc}
     */
    public function derive(string $namespace): FastCache
    {
        return new static($this->storage, $this->namespace . $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, callable $fallback = null, ?float $beta = null)
    {
        $data = $this->doFetch($this->generateKey($key));

        if ($data instanceof CacheItemInterface) {
            $data = $data->isHit() ? $data->get() : null;
        }

        if (null === $data && $fallback) {
            return $this->save($key, $fallback, $beta);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkLoad(array $keys, callable $fallback = null, ?float $beta = null): array
    {
        if (0 === \count($keys)) {
            return [];
        }

        foreach ($keys as $key) {
            if (!\is_scalar($key)) {
                throw new InvalidArgumentException('Only scalar keys are allowed in bulkLoad()');
            }
        }
        $storageKeys = \array_map([$this, 'generateKey'], $keys);
        $cacheData   = $this->doFetch($storageKeys, true);
        $result      = [];

        if ($cacheData instanceof Generator) {
            $cacheData = \iterator_to_array($cacheData);
        }

        foreach ($keys as $i => $key) {
            $storageKey = $storageKeys[$i];

            if (isset($cacheData[$storageKey])) {
                $result[$key] = $cacheData[$storageKey];
            } elseif ($fallback) {
                $result[$key] = $this->save(
                    $key,
                    function (CacheItemInterface $item, bool $save) use ($key, $fallback) {
                        return $fallback(...[$key, &$item, &$save]);
                    },
                    $beta
                );
            } else {
                $result[$key] = null;
            }
        }

        return \array_map(
            function ($value) {
                if ($value instanceof CacheItemInterface) {
                    return $value->get();
                }

                return $value;
            },
            $result
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, ?callable $callback = null, ?float $beta = null)
    {
        $key = $this->generateKey($key);

        if (null === $callback) {
            $this->doDelete($key);

            return false;
        }

        if (0 > $beta = $beta ?? 1.0) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Argument "$beta" provided to "%s::get()" must be a positive number, %f given.',
                    static::class,
                    $beta
                )
            );
        }

        static $setExpired;

        $setExpired = Closure::bind(
            static function (CacheItem $item) {
                if (null === $item->expiry) {
                    return null;
                }

                return (int) (0.1 + $item->expiry - \microtime(true));
            },
            null,
            CacheItem::class
        );

        if ($this->storage instanceof PhpCachePool) {
            $setExpired = static function (PhpCacheItem $item) {
                return $item->getExpirationTimestamp();
            };
        }

        $callback = function (CacheItemInterface $item, bool $save) use ($key, $callback) {
            // don't wrap nor save recursive calls
            if (isset($this->computing[$key])) {
                $value = $callback(...[&$item, &$save]);
                $save  = false;

                return $value;
            }

            $this->computing[$key] = $key;

            try {
                return $value = $callback(...[&$item, &$save]);
            } catch (Throwable $e) {
                $this->doDelete($key);

                throw $e;
            } finally {
                unset($this->computing[$key]);
            }
        };

        return $this->doSave($this->storage, $key, $callback, $setExpired, $beta);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): void
    {
        $this->save($key, null);
    }

    /**
     * {@inheritdoc}
     */
    public function call(callable $callback)
    {
        $key = \func_get_args();

        if (\is_array($callback) && \is_object($callback[0])) {
            $key[0][0] = \get_class($callback[0]);
        }

        return $this->load($key, function (CacheItemInterface $item, bool $save) use ($callback, $key) {
            $dependencies = \array_merge(\array_slice($key, 1), [&$item, &$save]);

            return $callback(...$dependencies);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $callback, ?float $beta = null): callable
    {
        return function () use ($callback, $beta) {
            $key = [$callback, \func_get_args()];

            if (\is_array($callback) && \is_object($callback[0])) {
                $key[0][0] = \get_class(\current($callback));
            }

            if (null === $data = $this->load($key)) {
                $data = $this->save(
                    $key,
                    function (CacheItemInterface $item, bool $save) use ($callback, $key) {
                        $dependencies = \array_merge($key[1], [&$item, &$save]);

                        return $callback(...$dependencies);
                    },
                    $beta
                );
            }

            return $data;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function start($key): ?OutputHelper
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
     *
     * @return string
     */
    protected function generateKey($key): string
    {
        if (\is_array($key) && \current($key) instanceof Closure) {
            $key = \spl_object_id($key[0]);
        }

        $key = \md5(\is_scalar($key) ? (string) $key : \serialize($key));

        return \strpos($this->namespace, '%s') ? \sprintf($this->namespace, $key) : $this->namespace . $key;
    }

    /**
     * Save cache item.
     *
     * @param CacheInterface|CacheItemPoolInterface $storage
     * @param string                                $key
     * @param Closure                               $callback
     * @param Closure                               $setExpired
     * @param null|float                            $beta
     *
     * @return mixed The corresponding values found in the cache
     */
    protected function doSave($storage, string $key, Closure $callback, Closure $setExpired, ?float $beta)
    {
        if ($storage instanceof CacheItemPoolInterface) {
            $item = $this->storage->getItem($key);

            if (!$item->isHit() || \INF === $beta) {
                $save   = true;
                $result = $callback(...[$item, $save]);

                if (true === $save) {
                    if (!$result instanceof CacheItemInterface) {
                        $item->set($result);
                        $this->storage->save($item);
                    } else {
                        $this->storage->save($result);
                    }
                }
            }

            return $item->get();
        }

        $save   = true;
        $item   = $storage instanceof PhpCachePool ? new PhpCacheItem($key) : new CacheItem();
        $result = $callback(...[$item, $save]);

        if ($result instanceof CacheItemInterface) {
            $result = $result->get();
        }

        $storage->set($key, $result, $setExpired($item));

        return $result;
    }

    /**
     * Fetch cache item.
     *
     * @param array|string $id The cache identifier to fetch
     *
     * @return mixed The corresponding values found in the cache
     */
    protected function doFetch($ids, bool $multiple = false)
    {
        if ($this->storage instanceof CacheItemPoolInterface) {
            return !$multiple ? $this->storage->getItem($ids) : $this->storage->getItems($ids);
        }

        return !$multiple ? $this->storage->get($ids) : $this->storage->getMultiple($ids, new stdClass());
    }

    /**
     * Remove an item from cache.
     *
     * @param string $id An identifier that should be removed from cache
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete(string $id)
    {
        if ($this->storage instanceof CacheItemPoolInterface) {
            return $this->storage->deleteItem($id);
        }

        return $this->storage->delete($id);
    }
}
