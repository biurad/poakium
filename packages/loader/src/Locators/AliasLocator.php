<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Loader\Locators;

/**
 * The Alias Manager.
 *
 * Register the array of class or namespace aliases in an application.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 */
class AliasLocator
{
    /** @var array<int,string> */
    private array $namespaces = [];

    /** @var array<int,string> */
    private array $classes = [];

    public function __construct(array $aliases = [])
    {
        foreach ($aliases as $alias => $classOrNamespace) {
            $this->add($classOrNamespace, $alias);
        }
    }

    /**
     * Add a class or namespace alias.
     *
     * @param string $classOrNamespace A namespace must end with a backslash
     */
    public function add(string $classOrNamespace, string $alias): void
    {
        if (\str_ends_with($classOrNamespace, '\\')) {
            if (!\str_ends_with($alias, '\\')) {
                throw new \InvalidArgumentException(\sprintf("Alias '%s' must end with a backslash", $alias));
            }

            $this->namespaces[$alias] = $classOrNamespace;
        } elseif (\class_exists($classOrNamespace)) {
            $this->classes[$alias] = $classOrNamespace;
        }
    }

    /**
     * Autoload namespaces and classes using PHP class_alias function.
     */
    public function register(): void
    {
        static $loaded = new \ArrayObject();
        \spl_autoload_register(function (string $class) use (&$loaded): void {
            if (isset($loaded[$class])) {
                return;
            }

            if (isset($this->classes[$class])) {
                $loaded[$class] = \class_alias($this->classes[$class], $class);
                return;
            }

            foreach ($this->namespaces as $alias => $namespace) {
                if (\str_starts_with($class, $alias)) {
                    $loaded[$class] = \class_alias($namespace.\substr($class, \strlen($alias)), $class);
                    break;
                }
            }
        });
    }
}
