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

use InvalidArgumentException;

interface FileLocatorInterface
{
    /**
     * Fiind all files in a list of directories.
     *
     * @param array|string $extension
     *
     * @return null|iterable
     */
    public function findFiles(string $extension = 'php'): ?iterable;

    /**
     * Fiind all directories in a list of directories.
     *
     * @return null|iterable
     */
    public function findDirectories(): ?iterable;

    /**
     * Returns a full path for a given file name.
     *
     * @param string      $name        The file name to locate
     * @param null|string $currentPath The current path
     * @param bool        $first       Whether to return the first occurrence or an array of filenames
     *
     * @throws InvalidArgumentException         If $name is empty
     * @throws FileLocatorFileNotFoundException If a file is not found
     *
     * @return array|string The full path to the file or an array of file paths
     */
    public function locate(string $name, string $currentPath = null, bool $first = true);
}
