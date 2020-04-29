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
use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use Doctrine\Common\Cache\{FlushableCache, MultiOperationCache, Cache as DoctrineCache};

use function is_iterable;
use function iterator_to_array;

class SimpleCache implements CacheInterface
{
    /**
     * @var DoctrineCache
     */
    protected $instance;

    /**
     * @var EventDispatcherInterface
     */
    protected $event;

    /**
     * @var string
     */
    protected $driver = null;

    /**
     * Cache Constructor.
     *
     * @param DoctrineCache|string $instance
     * @param EventDispatcherInterface|null       $event
     */
    public function __construct(DoctrineCache $instance, EventDispatcherInterface $event = null)
    {
        $this->event = $event;
        $this->instance = $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
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
    public function set($key, $value, $ttl = 0)
    {
        return $this->instance->save($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->instance->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
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
    public function setMultiple($values, $ttl = null)
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
    public function deleteMultiple($keys)
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
     * Get the value of event.
     *
     * @return EventDispatcherInterface
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set the driver instance
     *
     * @param $instance
     * @return  self
     */
    public function setInstance($instance)
    {
        $this->driver = $instance;

        return $this;
    }
}
