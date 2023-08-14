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

use Symfony\Component\VarDumper\Tests\Fixtures\NotLoadableClass;

/**
 * Can locate classes in a specified directory.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 */
final class ClassLocator
{
    public function __construct(private FileLocator $finder)
    {
        if (!\function_exists('token_get_all')) {
            throw new \LogicException('The Tokenizer extension is required for the class loading.');
        }
    }

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     *
     * @param mixed $target Class, interface, trait, or namespace\\. By default - null (all classes).
     *                      Parent (class) will also be included to classes list as one of
     *                      results.
     *
     * @return iterable|\ReflectionClass[]|null
     *
     * @throws \ReflectionException
     */
    public function getClasses(object|string $target = null): ?iterable
    {
        if (!empty($target) && (\is_object($target) || \is_string($target))) {
            $target = new \ReflectionClass($target);
        }

        foreach ($this->availableReflections() as $class) {
            if (null === $class || NotLoadableClass::class === $class) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$this->isTargeted($reflection, $target) || $reflection->isInterface()) {
                continue;
            }

            yield from [$reflection->getName() => $reflection];
        }

        \gc_mem_caches();

        return null;
    }

    /**
     * Extract the class name from the file at the given path.
     */
    public function findClass(string $path): ?string
    {
        $tokens = \token_get_all(\file_get_contents($path));
        $namespace = '';
        $namespaced = 0;

        foreach ($tokens as $token) {
            if (\T_NAMESPACE === $token[0]) {
                $namespaced = 1;
            } elseif (\T_NAME_QUALIFIED === $token[0] && 1 === $namespaced) {
                $namespace = $token[1].'\\';
                $namespaced = 0;
            } elseif (\T_DOUBLE_COLON === $token[0] || \T_NEW === $token[0]) {
                $namespaced = 3; // Skip usage of ::class constant and anonymous classes
            } elseif (\T_CLASS === $token[0] && 0 === $namespaced) {
                $namespaced = 2;
            } elseif (\T_STRING === $token[0] && 2 === $namespaced) {
                return $namespace.$token[1];
            }
        }

        return null;
    }

    /**
     * Available file reflections. Generator.
     *
     * @return array<int,string>
     */
    protected function availableReflections(): array
    {
        $classes = [];

        foreach ($this->finder->findFiles('php') as $splFileInfo) {
            $classes[] = $this->findClass($splFileInfo->getPathname());
        }

        return $classes;
    }

    /**
     * Get every class trait (including traits used in parents).
     */
    protected function fetchTraits(string $class): array
    {
        $traits = [];

        while ($class) {
            $traits = \array_merge(\class_uses($class), $traits);
            $class = \get_parent_class($class);
        }

        // Traits from traits
        foreach (\array_flip($traits) as $trait) {
            $traits = \array_merge(\class_uses($trait), $traits);
        }

        return \array_unique($traits);
    }

    /**
     * Classes available in finder scope.
     */
    protected function availableClasses(): array
    {
        $classes = [];

        foreach ($this->availableReflections() as $class) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                // Ignoring
                continue;
            }

            $classes[] = $reflection->getName();
        }

        return $classes;
    }

    /**
     * Check if given class targeted by locator.
     */
    protected function isTargeted(\ReflectionClass $class, \ReflectionClass $target = null): bool
    {
        if (empty($target)) {
            return true;
        }

        if (!$target->isTrait()) {
            // Target is interface or class
            return $class->isSubclassOf($target) || $class->getName() == $target->getName();
        }

        // Checking using traits
        return \in_array($target->getName(), $this->fetchTraits($class->getName()), true);
    }
}
