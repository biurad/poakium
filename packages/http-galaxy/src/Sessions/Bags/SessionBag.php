<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Sessions\Bags;

use Biurad\Http\Interfaces\SessionDataInterface;

/**
 * Represents part of $this->attributes array.
 */
class SessionBag implements SessionDataInterface
{
    /**
     * The session attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /** @var string */
    private $storageKey;

    /**
     * Reference to this$this->attributes segment.
     *
     * @var string
     */
    private $name = '__attributes';

    /**
     * @param string $storageKey The key used to store attributes in the session
     */
    public function __construct(string $storageKey = '_bf_attributes')
    {
        $this->storageKey = $storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$attributes): void
    {
        $this->attributes = &$attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return \count($this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name, $default = null)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function pull(string $name, $default = null)
    {
        $value = $this->get($name, $default);
        $this->delete($name);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name)
    {
        $retval = null;

        if ($this->has($name)) {
            $retval = $this->attributes[$name];
            unset($this->attributes[$name]);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name)
    {
        $retval = null;

        if (\array_key_exists($name, $this->attributes)) {
            $retval = $this->attributes[$name];
            unset($this->attributes[$name]);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $return = $this->attributes;
        $this->attributes = [];

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes): void
    {
        $this->attributes = [];

        foreach ($attributes as $key => $value) {
            $this->add($key, $value);
        }
    }

    /**
     * Increment the value of an item in the session.
     *
     * @param string $key
     * @param int    $amount
     *
     * @return mixed
     */
    public function increment($key, $amount = 1)
    {
        $this->add($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    /**
     * Decrement the value of an item in the session.
     *
     * @param string $key
     * @param int    $amount
     *
     * @return int
     */
    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, $amount * -1);
    }
}
