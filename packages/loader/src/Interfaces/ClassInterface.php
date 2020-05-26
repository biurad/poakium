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

use ReflectionClass;

interface ClassInterface
{
    /**
     * Extract the class name from the file at the given path.
     *
     * @param  string  $path
     *
     * @return string|null
     */
    public function findClass(string $path): ?string;

    /**
     * Fiind all files in a list of directories.
     *
     * @param string $extension
     *
     * @return array
     */
    public function findFiles(string $extension = '.php'): array;

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * @param mixed $target  Class, interface or trait parent. By default - null (all classes).
     *                       Parent (class) will also be included to classes list as one of
     *                       results.
     *
     * @return ReflectionClass[]
     */
    public function getClasses($target = null): array;
}
