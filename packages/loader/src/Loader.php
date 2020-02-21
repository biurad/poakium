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
 * @link      https://www.biurad.com/projects/loadermanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader;

final class Loader
{
    /**
     * @var array loader instances
     */
    public static $instances = [];

    /**
     * @param string $type
     */
    public static function instance($type)
    {
        return self::driver($type);
    }

    /**
     * Creates a class singleton loader of the given type.
     *
     * Supported adapters (config, alias, file).
     *
     * ```php
     * Loader::instance('alias')->register();
     * ```
     *`
     * @param string $type type of loader (config, alias, etc)
     *
     * @internal
     */
    public static function driver($type)
    {

        if (!isset(self::$instances[$type])) {
            // Set the loader class name
            $class = __NAMESPACE__.'\\'.ucfirst($type).'Loader';

            // Create a new loader instance
            self::$instances[$type] = new $class();
        }

        return self::$instances[$type];
    }
}
