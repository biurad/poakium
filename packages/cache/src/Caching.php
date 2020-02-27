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

namespace BiuradPHP\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Implements the cache for a application.
 */
class Caching
{
	/** dependency */
	public const
		PRIORITY = 'priority',
		EXPIRATION = 'expire',
		EXPIRE = 'expire',
		CALLBACKS = 'callbacks',
		NAMESPACES = 'namespaces',
        ALL = 'all',
        YEAR = 31557600; //average year in seconds

	/** @internal */
    public const NAMESPACE_SEPARATOR = "\x00";
    public const NAMESPACE = 'CACHE_KEY[%s]';

	/** @var CacheInterface */
	private $storage;

	/** @var string */
	private $namespace;


    /**
     * @param CacheInterface $storage
     * @param string $namespace
     */
	public function __construct(CacheInterface $storage, string $namespace = self::NAMESPACE)
	{
		$this->storage = $storage;
		$this->namespace = $namespace . self::NAMESPACE_SEPARATOR;
	}

	/**
	 * Returns cache storage.
	 */
	final public function getStorage(): CacheInterface
	{
		return $this->storage;
	}

	/**
	 * Returns cache namespace.
	 */
	final public function getNamespace(): string
	{
		return (string) substr(sprintf($this->namespace, null), 0, -1);
	}

	/**
	 * Returns new nested cache object.
     *
	 * @return static
	 */
	public function derive(string $namespace)
	{
        $derived = new static($this->storage, $this->namespace . $namespace);

		return $derived;
	}

	/**
	 * Reads the specified item from the cache or generate it.
     *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function load($key, callable $fallback = null)
	{
        $data = $this->storage->get($this->generateKey($key));

		if ($data === null && $fallback) {
			return $this->save($key, function (&$dependencies) use ($fallback) {
				return $fallback(...[&$dependencies]);
			});
        }

		return $data;
	}

	/**
	 * Reads multiple items from the cache.
	 */
	public function bulkLoad(array $keys, callable $fallback = null): array
	{
		if (count($keys) === 0) {
			return [];
		}
		foreach ($keys as $key) {
			if (!is_scalar($key)) {
				throw new \InvalidArgumentException('Only scalar keys are allowed in bulkLoad()');
			}
		}
		$storageKeys = array_map([$this, 'generateKey'], $keys);

		$cacheData = $this->storage->getMultiple($storageKeys);
		$result = [];
		foreach ($keys as $i => $key) {
			$storageKey = $storageKeys[$i];
			if (isset($cacheData[$storageKey])) {
				$result[$key] = $cacheData[$storageKey];
			} elseif ($fallback) {
				$result[$key] = $this->save($key, function (&$dependencies) use ($key, $fallback) {
					return $fallback(...[$key, &$dependencies]);
				});
			} else {
				$result[$key] = null;
			}
		}
		return $result;
	}

	/**
	 * Writes item into the cache.
	 * Dependencies are:
	 * - Caching::NAMESPACE => extra name added to key
	 * - Caching::EXPIRATION => (timestamp) expiration
	 * - Caching::EXPIRE => (timestamp) expiration
	 *
	 * @param  mixed  $key
	 * @param  mixed  $data
	 * @return mixed  value itself
     *
	 * @throws \InvalidArgumentException
	 */
	public function save($key, $data, array $dependencies = null)
	{
        $key = $this->generateKey($key);

		if ($data instanceof \Closure) {
			try {
				$data = $data(...[&$dependencies]);
			} catch (\Throwable $e) {
				$this->storage->delete($key);
				throw $e;
			}
        }

		if ($data === null) {
			$this->storage->delete($key);
		} else {
            $dependencies = $this->completeDependencies($dependencies, $data);

            if (isset($dependencies[self::CALLBACKS])) {
                $data = $dependencies[self::CALLBACKS][0];
            }

			if (isset($dependencies[self::EXPIRATION]) && $dependencies[self::EXPIRATION] <= 0) {
				$this->storage->delete($key);
			} else {
				$this->storage->set($key, $data, isset($dependencies[self::EXPIRATION]) ? $dependencies[self::EXPIRATION] : null);
            }

			return $data;
		}
	}

	private function completeDependencies(?array $dp, $data): array
	{
		// convert expire into relative amount of seconds
		if (isset($dp[self::EXPIRATION])) {
            $time = $dp[self::EXPIRATION];
            if ($time instanceof \DateTimeInterface) {
                $time = new \DateTime($time->format('Y-m-d H:i:s.u'), $time->getTimezone());
            } elseif (is_numeric($time)) {
                if ($time <= self::YEAR) {
                    $time += time();
                }
                $time = new \DateTime('@' . $time, new \DateTimeZone(date_default_timezone_get()));

            } else { // textual or null
                $time = new \DateTime((string) $time);
            }
			$dp[self::EXPIRATION] = $time->format('U') - time();
		}

		// make list from NAMESPACES
		if (isset($dp[self::NAMESPACES])) {
			$dp[self::NAMESPACES] = array_values((array) $dp[self::NAMESPACES]);
        }

        // covert CALLBACKS into values
        if (isset($dp[self::CALLBACKS])) {
            $dp[self::CALLBACKS] = [$dp[self::CALLBACKS]];

            // If empty data value get from callable.
            if (!isset($dp[self::CALLBACKS][0][1])) {
                $dp[self::CALLBACKS][0][1] = $data;
            }
            $dp[self::CALLBACKS] = iterator_to_array($this->checkCallbacks($dp[self::CALLBACKS]));
        }

		if (!is_array($dp)) {
			$dp = [];
        }

		return $dp;
	}

	/**
	 * Removes item from the cache.
	 * @param  mixed  $key
	 */
	public function remove($key): void
	{
		$this->save($key, null);
	}

	/**
	 * Caches results of function/method calls.
	 * @return mixed
	 */
	public function call(callable $function)
	{
		$key = func_get_args();
		if (is_array($function) && is_object($function[0])) {
			$key[0][0] = get_class($function[0]);
		}
		return $this->load($key, function () use ($function, $key) {
			return $function(...array_slice($key, 1));
		});
	}

	/**
	 * Caches results of function/method calls.
     *
     * Dependencies are:
	 * - Caching::CALLBACKS => callables passed around data
	 */
	public function wrap(callable $function, array $dependencies = null): \Closure
	{
		return function () use ($function, $dependencies) {
			$key = [$function, func_get_args()];
			if (is_array($function) && is_object($function[0])) {
				$key[0][0] = get_class($function[0]);
			}
            $data = $this->load($key);

			if ($data === null) {
				$data = $this->save($key, $function(...$key[1]), $dependencies);
            }

			return $data;
		};
	}

	/**
	 * Starts the output cache.
	 * @param  mixed  $key
	 */
	public function start($key): ?OutputHelper
	{
		$data = $this->load($key);
		if ($data === null) {
			return new OutputHelper($this, $key);
		}
        echo $data;

		return null;
	}

	/**
	 * Generates internal cache key.
	 */
	protected function generateKey($key): string
	{
        $key = md5((is_scalar($key) || $key instanceof \Closure) ? (string) $key : serialize($key));

		return strpos($this->namespace, '%s') ? sprintf($this->namespace, $key) : $this->namespace . $key;
	}

	/**
	 * Checks CALLBACKS dependencies.
	 */
	public function checkCallbacks(array $callbacks): iterable
	{
		foreach ($callbacks as $callback) {
			yield array_shift($callback)(...$callback);
		}
	}
}
