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

use Composer\Autoload\ClassLoader;

/**
 * Get All Paths from composer directory
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
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
