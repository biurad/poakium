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

namespace BiuradPHP\Loader\Locators;

use ArrayObject;
use BiuradPHP\Loader\Aliases\ClassAlias;
use BiuradPHP\Loader\Aliases\NamespaceAlias;
use BiuradPHP\Loader\Interfaces\AliasInterface;
use BiuradPHP\Loader\Interfaces\AliasTypeInterface;

/**
 * The Alias Manager.
 *
 * Register the array of class or namespace aliases in an application.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
class AliasLocator implements AliasInterface
{
    private $namespaces = [];

    private $classes = [];

    public function __construct(array $aliases = [])
    {
        $this->setAliases($aliases);
    }

    public function addAliasType(AliasTypeInterface $alias): AliasInterface
    {
        if ($alias instanceof NamespaceAlias) {
            $this->namespaces = \array_merge($this->namespaces, $alias->getAlias());

            return $this;
        }

        $this->classes = \array_merge($this->classes, $alias->getAlias());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias(string $classOrNamespace, string $alias): AliasInterface
    {
        if (\class_exists($classOrNamespace)) {
            return $this->addAliasType(new ClassAlias($alias, $classOrNamespace));
        }

        return $this->addAliasType(new NamespaceAlias($alias, $classOrNamespace));
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $loaded = new ArrayObject([]);

        \spl_autoload_register(self::createPrependAutoloader(
            $this->classes,
            $loaded
        ), true, true);

        \spl_autoload_register(self::createAppendAutoloader(
            $this->namespaces,
            $loaded
        ));
    }

    /**
     * Set the registered aliases.
     *
     * @param array $aliases [$alias => $$classOrNamespace]
     */
    public function setAliases(array $aliases): void
    {
        foreach ($aliases as $alias => $classOrNamespace) {
            $this->addAlias($classOrNamespace, $alias);
        }
    }

    /**
     * @return callable
     */
    private static function createPrependAutoloader(array $classes, ArrayObject $loaded)
    {
        /*
         * @param  string $class Class name to autoload
         * @return bool|null
         */
        return static function ($class) use ($classes, $loaded) {
            if (isset($loaded[$class])) {
                return null;
            }

            if (isset($classes[$class])) {
                return \class_alias($classes[$class], $class);
            }

            return null;
        };
    }

    /**
     * @return callable
     */
    private static function createAppendAutoloader(array $namespaces, ArrayObject $loaded)
    {
        /*
         * @param  string $class Class name to autoload
         * @return void
         */
        return static function ($class) use ($namespaces, $loaded) {
            $segments = \explode('\\', $class);

            $i     = 0;
            $check = '';

            // We are checking segments of the namespace to match quicker
            while (isset($segments[$i + 1], $namespaces[$check . $segments[$i] . '\\'])) {
                $check .= $segments[$i] . '\\';
                ++$i;
            }

            if ($check === '') {
                return null;
            }

            $alias = $namespaces[$check]
                . \strtr(\substr($class, \strlen($check)), $namespaces);

            $loaded[$alias] = true;

            if (\class_exists($alias) || \interface_exists($alias) || \trait_exists($alias)) {
                return \class_alias($alias, $class);
            }
        };
    }
}
