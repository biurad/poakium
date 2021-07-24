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
 * FlashBag flash message container.
 *
 * @author Drak <drak@zikula.org>
 */
class FlashBag implements SessionDataInterface
{
    private $name = '__flashes';

    private $flashes = [];

    private $storageKey;

    /**
     * @param string $storageKey The key used to store flashes in the session
     */
    public function __construct(string $storageKey = '_bf_flashes')
    {
        $this->storageKey = $storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$flashes): void
    {
        $this->flashes = &$flashes;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $type, $message): void
    {
        $this->flashes[$type][] = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function peek(string $type, array $default = [])
    {
        return $this->has($type) ? $this->flashes[$type] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $type, $default = [])
    {
        if (!$this->has($type)) {
            return $default;
        }

        $return = $this->flashes[$type];

        unset($this->flashes[$type]);

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $type): bool
    {
        return \array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->flashes);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function pull(string $name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name)
    {
        $retval = null;

        if ($this->has($name)) {
            $retval = $this->flashes[$name];
            unset($this->flashes[$name]);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name)
    {
        $retval = null;

        if (\array_key_exists($name, $this->flashes)) {
            $retval = $this->flashes[$name];
            unset($this->flashes[$name]);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $flashes): void
    {
        $this->flashes = [];

        foreach ($flashes as $key => $value) {
            $this->add($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->all();
    }

    /**
     * Returns an iterator for flashes.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->flashes);
    }

    /**
     * Returns the number of flashes.
     *
     * @return int The number of flashes
     */
    public function count()
    {
        return \count($this->flashes);
    }

    /**
     * {@inheritdoc}
     */
    private function all()
    {
        $return = $this->flashes;
        $this->flashes = [];

        return $return;
    }
}
