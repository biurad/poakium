<?php

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
 * @link      https://www.biurad.com/projects/loadermanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Interfaces;

/**
 * Adapter for reading and writing configuration files.
 */
interface AdapterInterface
{

	/**
     * Read from a file and create an array
     *
     * @param  string $filename
     *
     * @return array
     */
    public function fromFile(string $filename);

    /**
     * Read from a string and create an array
     *
     * @param  string[]|string $string
     *
     * @return array|bool
     */
    public function fromString($string);

    /**
     * Generates configuration string.
     *
     * Write a config object to a string.
     *
     * @param  mixed $config
     */
    public function dump($config): string;
}
