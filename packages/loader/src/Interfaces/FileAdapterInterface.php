<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/biurad-loader
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Interfaces;

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
     * @param array|object|\Traversable|\JsonSerializable $config
     *
     * @return string
     */
    public function dump($config): string;
}
