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

namespace BiuradPHP\Cache\Interfaces;

use BiuradPHP\Cache\FastCache;
use BiuradPHP\Cache\OutputHelper;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * FastCache provides a consistent interface to Caching in your application. It allows you
 * to use either psr6 or psr16 implementation, allowing less limitations, and fast caching.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface FastCacheInterface
{
    /**
     * Returns cache storage.
     *
     * @return CacheInterface|CacheItemPoolInterface
     */
    public function getStorage();

    /**
     * Returns new nested cache object.
     *
     * @param string $namespace
     *
     * @return FastCache
     */
    public function derive(string $namespace): FastCache;

    /**
     * Reads the specified item from the cache or generate it.
     *
     * @param mixed         $key
     * @param null|callable $fallback
     * @param null|float    $beta
     *
     * @return mixed
     */
    public function load($key, callable $fallback = null, ?float $beta = null);

    /**
     * Reads multiple items from the cache.
     *
     * @param array<string,mixed> $keys
     * @param null|callable       $fallback
     * @param null|float          $beta
     *
     * @return array<string,mixed>
     */
    public function bulkLoad(array $keys, callable $fallback = null, ?float $beta = null): array;

    /**
     * Writes an item into the cache.
     *
     * @param mixed         $key
     * @param null|callable $callback
     * @param null|float    $beta
     *
     * @return mixed value itself
     */
    public function save($key, ?callable $callback = null, ?float $beta = null);

    /**
     * Remove an item from the cache.
     *
     * @param mixed $key
     */
    public function delete($key): void;

    /**
     * Caches results of function/method calls.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function call(callable $callback);

    /**
     * Caches results of function/method calls
     *
     * @param callable   $callback
     * @param null|float $beta
     *
     * @return callable so arguments can be passed into for final results
     */
    public function wrap(callable $callback, ?float $beta = null);

    /**
     * Starts the output cache.
     *
     * @param mixed $key
     *
     * @return null|OutputHelper
     */
    public function start($key): ?OutputHelper;
}
