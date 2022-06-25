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

use JsonSerializable;
use Traversable;

/**
 * Adapter for reading and writing configuration files.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
interface FileAdapterInterface
{
    /**
     * Check file supported extensions
     *
     * @param string $file
     *
     * @return bool
     */
    public function supports(string $file): bool;

    /**
     * Read from a file and create an array
     *
     * @param string $filename
     *
     * @return array
     */
    public function fromFile(string $filename): array;

    /**
     * Read from a string and create an array
     *
     * @param string $string
     *
     * @return array
     */
    public function fromString(string $string): array;

    /**
     * Generates configuration string.
     *
     * Write a config object to a string.
     *
     * @param array|JsonSerializable|object|Traversable $config
     *
     * @return string
     */
    public function dump($config): string;
}
