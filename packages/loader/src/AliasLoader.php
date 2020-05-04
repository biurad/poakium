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

namespace BiuradPHP\Loader;

use BiuradPHP;
use BiuradPHP\Loader\Interfaces\AliasInterface;

/**
 * The Alias Manager.
 *
 * Register the array of class aliases when this application
 * is started.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
class AliasLoader implements AliasInterface
{
    /**
     * The array of class aliases.
     *
     * @var array
     */
    protected $aliases;

    /**
     * The singleton instance of the loader.
     *
     * @var BiuradPHP\Loader\AliasLoader
     */
    protected static $instance;

    /**
     * Create a new AliasLoader instance.
     *
     * @param array $aliases
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
    }

    /**
     * {@inheritdoc}
     */
    public static function getInstance(array $aliases = []): AliasInterface
    {
        if (null === static::$instance) {
            return static::$instance = new static($aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
     *
     * @param string $alias
     * @return bool|null
     */
    public function load($alias): ?bool
    {
        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        return $this->prependToLoaderStack();
    }

    /**
     * Prepend the load method to the auto-loader stack.
     */
    protected function prependToLoaderStack()
    {
        spl_autoload_register([$this, 'load'], true, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set the registered aliases.
     *
     * @param array $aliases
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Set the value of the singleton alias loader.
     *
     * @param BiuradPHP\Loader\AliasLoader $loader
     */
    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }
}
