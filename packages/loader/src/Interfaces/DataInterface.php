<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Loader\Interfaces;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;

interface DataInterface extends Countable, Iterator, ArrayAccess, JsonSerializable
{
    /**
     * Return an associative array of the stored data.
     *
     * @return array
     */
    public function toArray();

    /**
     * Retrieve a value and return $default if there is no element set.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Merge another Config with this one.
     *
     * For duplicate keys, the following will be performed:
     * - Nested Configs will be recursively merged.
     * - Items in $merge with INTEGER keys will be appended.
     * - Items in $merge with STRING keys will overwrite current values.
     *
     * @param DataInterface $merge
     *
     * @return self
     */
    public function merge(DataInterface $merge);

    /**
     * Prevent any more modifications being made to this instance.
     *
     * Useful after merge() has been used to merge multiple Config objects
     * into one object which should then not be modified again.
     */
    public function setReadOnly();

    /**
     * Allow modifications being made to this instance.
     */
    public function setWritable();

    /**
     * Returns whether this Config object is read only or not.
     *
     * @return bool
     */
    public function isReadOnly();

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param array|object|string $source could be file or array
     * @param string              $name   key name to load as file
     *
     * @return $this|array
     */
    public function mergeFrom($source, $name);
}
