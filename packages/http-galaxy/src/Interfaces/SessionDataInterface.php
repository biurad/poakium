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

namespace Biurad\Http\Interfaces;

use IteratorAggregate;

/**
 * Session Bag store.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface SessionDataInterface extends SessionBagInterface, IteratorAggregate
{
    public function replace(array $attributes): void;

    /**
     * Set data in session.
     *
     * @param mixed $value
     */
    public function add(string $name, $value): void;

    /**
     * Check if value presented in session.
     */
    public function has(string $name): bool;

    /**
     * Get value stored in session.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * Read item from session and delete it after.
     *
     * @param mixed $default default value when no such item exists
     *
     * @return mixed
     */
    public function pull(string $name, $default = null);

    /**
     * Delete data from session.
     *
     * @return mixed
     */
    public function delete(string $name);

    /**
     * Clear all session section data.
     *
     * @return mixed
     */
    public function clear();
}
