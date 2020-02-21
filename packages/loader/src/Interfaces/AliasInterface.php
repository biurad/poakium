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

interface AliasInterface
{
    /**
     * Get or create the singleton alias loader instance.
     *
     * @param  array  $aliases
     */
    public static function getInstance(array $aliases = []): AliasInterface;

    /**
     * Add an alias to the loader.
     *
     * @param  string  $class
     * @param  string  $alias
     */
    public function alias($class, $alias);

    /**
     * Get the registered aliases.
     */
    public function getAliases(): array;

    /**
     * Register the loader on the auto-loader stack.
     *
     * @return void
     */
    public function register();
}
