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

use Composer\Autoload\ClassLoader;

/**
 * Get All Paths from composer directory
 */
interface ComposerInterface
{
    /**
     * Get the full path of composer vendor directory
     */
    public function getPath(): string;

    /**
     * Get All Found Paths
     */
    public function getPaths(): iterable;

    /**
     * Get the Composer's ClassLoader instance
     *
     * @param bool $spl_functiion
     */
    public function getClassLoader(bool $spl_functiion = false): ClassLoader;
}
