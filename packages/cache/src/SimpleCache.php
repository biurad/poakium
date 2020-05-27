<?php /** @noinspection PhpInconsistentReturnPointsInspection */

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

use Traversable;
use Psr\SimpleCache\CacheInterface;
use Doctrine\Common\Cache\{FlushableCache, MultiOperationCache, Cache as DoctrineCache};

class SimpleCache implements CacheInterface
{
    /**
     * @var DoctrineCache
     */
    protected $instance;

    /**
     * @var string
     */
    protected $driver;

    /**
     * Cache Constructor.
     *
     * @param DoctrineCache|string $instance
     */
    public function __construct(DoctrineCache $instance)
    {
        $this->instance = $instance;
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
    public function set($key, $value, $ttl = 0): bool
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
        if ($this->instance instanceof FlushableCache) {
            return $this->instance->flushAll();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (is_iterable($keys) || $keys instanceof  Traversable) {
            $keys = iterator_to_array($keys);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->fetchMultiple($keys);
        }

        foreach ($keys as $key) {
            yield $this->get($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (is_iterable($values) || $values instanceof  Traversable) {
            $values = iterator_to_array($values);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->saveMultiple($values, $ttl);
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
        if (is_iterable($keys) || $keys instanceof  Traversable) {
            $keys = iterator_to_array($keys);
        }

        if ($this->instance instanceof MultiOperationCache) {
            return $this->instance->deleteMultiple($keys);
        }

        foreach ($keys as $key) {
            if (true !== $this->delete($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the driver instance
     *
     * @param $instance
     * @return  self
     */
    public function setInstance($instance): self
    {
        $this->driver = $instance;

        return $this;
    }
}
