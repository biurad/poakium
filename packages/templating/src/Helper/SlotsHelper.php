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

namespace Biurad\UI\Helper;

use InvalidArgumentException;
use LogicException;

/**
 * SlotsHelper manages template slots.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SlotsHelper extends Helper
{
    /** @var array<string,mixed> */
    protected $slots = [];

    /** @var string[] */
    protected $openSlots = [];

    /**
     * Starts a new slot.
     *
     * This method starts an output buffer that will be
     * closed when the stop() method is called.
     *
     * @throws InvalidArgumentException if a slot with the same name is already started
     */
    public function start(string $name): void
    {
        if (\in_array($name, $this->openSlots, true)) {
            throw new InvalidArgumentException(\sprintf('A slot named "%s" is already started.', $name));
        }

        $this->openSlots[]  = $name;
        $this->slots[$name] = '';

        \ob_start();
        \ob_implicit_flush(0);
    }

    /**
     * Stops a slot.
     *
     * @throws LogicException if no slot has been started
     */
    public function stop(): void
    {
        if (!$this->openSlots) {
            throw new LogicException('No slot started.');
        }

        $name = \array_pop($this->openSlots);

        $this->slots[$name] = \ob_get_clean();
    }

    /**
     * Returns true if the slot exists.
     *
     * @return bool
     */
    public function has(string $name)
    {
        return isset($this->slots[$name]);
    }

    /**
     * Gets the slot value.
     *
     * @param bool|string $default The default slot content
     *
     * @return string The slot content
     */
    public function get(string $name, $default = false)
    {
        return isset($this->slots[$name]) ? $this->slots[$name] : $default;
    }

    /**
     * Sets a slot value.
     */
    public function set(string $name, string $content): void
    {
        $this->slots[$name] = $content;
    }

    /**
     * Outputs a slot.
     *
     * @param bool|string $default The default slot content
     *
     * @return bool true if the slot is defined or if a default content has been provided, false otherwise
     */
    public function output(string $name, $default = false)
    {
        if (!isset($this->slots[$name])) {
            if (false !== $default) {
                echo $default;

                return true;
            }

            return false;
        }

        echo $this->slots[$name];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'slots';
    }
}
