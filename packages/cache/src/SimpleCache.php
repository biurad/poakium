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
use Doctrine\Common\Cache\Cache as DoctrineCache;
use Doctrine\Common\Cache\MultiOperationCache;
use DomainException;
use Error;
use ErrorException;
use Psr\SimpleCache\CacheInterface;
use Traversable;

class SimpleCache implements CacheInterface
{
    /**
     * @var DoctrineCache
     */
    protected $instance;

    /**
     * Cache Constructor.
     *
     * @param DoctrineCache|string $instance
     */
    public function __construct(DoctrineCache $instance)
    {
        $this->instance = $instance;
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
        return $this->instance->save($key, $value, $ttl);
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
        $namespace = $this->instance->getNamespace();

        return isset($namespace[0]) ? $this->instance->deleteAll() : $this->instance->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof Traversable) {
            $keys = \iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new InvalidArgumentException('Cache keys must be array or Traversable.');
        }

        if ($this->instance instanceof MultiOperationCache) {
            $unserializeCallbackHandler = \ini_set(
                'unserialize_callback_func',
                self::class . '::handleUnserializeCallback'
            );

            try {
                return $this->instance->fetchMultiple((array) $keys);
            } catch (Error $e) {
                $trace = $e->getTrace();

                if (isset($trace[0]['function']) && !isset($trace[0]['class'])) {
                    switch ($trace[0]['function']) {
                        case 'unserialize':
                        case 'apcu_fetch':
                        case 'apc_fetch':
                            throw new ErrorException(
                                $e->getMessage(),
                                $e->getCode(),
                                \E_ERROR,
                                $e->getFile(),
                                $e->getLine()
                            );
                    }
                }

                throw $e;
            } finally {
                \ini_set('unserialize_callback_func', $unserializeCallbackHandler);
            }
        }

        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if ($values instanceof Traversable) {
            $values = \iterator_to_array($values);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->saveMultiple((array) $values, $ttl);
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
            if (true !== $this->delete($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback($class): void
    {
        throw new DomainException('Class not found: ' . $class);
    }
}
