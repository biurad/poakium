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
use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use DateInterval;
use Doctrine\Common\Cache\Cache as DoctrineCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MultiOperationCache;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use Traversable;

class SimpleCache implements CacheInterface
{
    /** @var DoctrineCache */
    protected $instance;

    /**
     * PSR-16 Cache Constructor.
     *
     * @param DoctrineCache $instance
     */
    public function __construct(DoctrineCache $instance)
    {
        $this->instance = $instance;
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return $this->instance->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->instance->fetch($key);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            throw new InvalidArgumentException('Using \'DataInterval\' will be implemented in v1.0');
        }

        return $this->instance->save($key, $value, $ttl ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
    {
        return $this->instance->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $driver = $this->instance;

        if (!$driver instanceof CacheProvider) {
            return false;
        }

        try {
            return $driver->flushAll();
        } catch (Throwable $e) {
            return $driver->deleteAll();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof Traversable) {
            $keys = \iterator_to_array($keys, false);
        }

        if (!\is_array($keys)) {
            throw new InvalidArgumentException('Cache keys must be array or Traversable.');
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->fetchMultiple($keys);
        }

        foreach ($keys as $key) {
            yield from [$key => $this->get($key, $default)];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            throw new InvalidArgumentException('Using \'DataInterval\' will be implemented in v1.0');
        }

        if ($values instanceof Traversable) {
            $values = \iterator_to_array($values);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->saveMultiple($values, $ttl ?? 0);
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys): bool
    {
        if ($keys instanceof Traversable) {
            $keys = \iterator_to_array($keys, false);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->deleteMultiple((array) $keys);
        }

        foreach ($keys as $key) {
            if ($this->delete($key)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
