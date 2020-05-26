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

namespace BiuradPHP\Loader\Files;

use BiuradPHP\Loader\Exceptions\LoaderException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\VarDumper\Tests\Fixtures\NotLoadableClass;

/**
 * Can locate classes in a specified directory.
 */
final class ClassLoader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FileLoader */
    protected $finder = null;

    /**
     * @param FileLoader $finder
     */
    public function __construct(FileLoader $finder)
    {
        $this->finder = $finder;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses($target = null): array
    {
        if (!empty($target) && (is_object($target) || is_string($target))) {
            $target = new \ReflectionClass($target);
        }

        $result = [];
        foreach ($this->availableReflections() as $class) {
            if (null === $class || $class === NotLoadableClass::class) {
                continue;
            }

            try {
                $reflection = $this->classReflection($class);
            } catch (LoaderException $e) {
                //Ignoring
                continue;
            }

            if (!$this->isTargeted($reflection, $target) || $reflection->isInterface()) {
                continue;
            }

            $result[$reflection->getName()] = $reflection;
        }

        return $result;
    }

    /**
     * Find all the class and interface names in a given directory.
     *
     * @return array
     */
    public function findClasses(): array
    {
        $found = [];
        foreach ($this->finder->findFiles('.php') as $file) {
            $found[] = $this->findClass(@$file);
        }

        return $found;
    }

    /**
     * Extract the class name from the file at the given path.
     *
     * @param  string  $path
     *
     * @return string|null
     */
    public function findClass(string $path): ?string
    {
        $namespace = null;
        $tokens = token_get_all(file_get_contents($path));

        foreach ($tokens as $key => $token) {
            if ($this->tokenIsIncludeOrRequire($token)) {
                if (null !== $this->logger) {
                    //We are not analyzing files which has includes, it's not safe to require such reflections
                    $this->logger->warning(
                        sprintf("File `%s` has includes and excluded from analysis", $path),
                        compact('path')
                    );
                }

                continue;
            }

            if ($this->tokenIsNamespace($token)) {
                $namespace = $this->getNamespace($key + 2, $tokens);
            }

            if ($this->tokenIsClassOrInterface($token)) {
                return ltrim($namespace.'\\'.$this->getClass($key + 2, $tokens), '\\');
            }
        }

        return null;
    }

    /**
     * Available file reflections. Generator.
     *
     * @return ReflectionClass[]|\Generator
     */
    protected function availableReflections(): \Generator
    {
        foreach ($this->findClasses() as $class) {
            yield $class;
        }
    }

    /**
     * Safely get class reflection, class loading errors will be blocked and reflection will be
     * excluded from analysis.
     *
     * @param string $class
     *
     * @return \ReflectionClass
     */
    protected function classReflection(string $class): \ReflectionClass
    {
        $loader = function ($class) {
            if ($class == LoaderException::class) {
                return;
            }

            throw new LoaderException("Class '{$class}' can not be loaded");
        };

        //To suspend class dependency exception
        spl_autoload_register($loader);

        try {
            //In some cases reflection can thrown an exception if class invalid or can not be loaded,
            //we are going to handle such exception and convert it soft exception
            return new \ReflectionClass($class);
        } catch (\Throwable $e) {
            if ($e instanceof LoaderException && $e->getPrevious() != null) {
                $e = $e->getPrevious();
            }

            if (null !== $this->logger) {
                $this->logger->error(
                    sprintf("%s: %s in %s:%s", $class, $e->getMessage(), $e->getFile(), $e->getLine()),
                    ['error' => $e]
                );
            }

            throw new LoaderException($e->getMessage(), $e->getCode(), $e);
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    /**
     * Get every class trait (including traits used in parents).
     *
     * @param string $class
     *
     * @return array
     */
    protected function fetchTraits(string $class): array
    {
        $traits = [];

        while ($class) {
            $traits = array_merge(class_uses($class), $traits);
            $class = get_parent_class($class);
        }

        //Traits from traits
        foreach (array_flip($traits) as $trait) {
            $traits = array_merge(class_uses($trait), $traits);
        }

        return array_unique($traits);
    }

    /**
     * Classes available in finder scope.
     *
     * @return array
     */
    protected function availableClasses(): array
    {
        $classes = [];

        foreach ($this->availableReflections() as $class) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                //Ignoring
                continue;
            }

            $classes[] = $reflection->getName();
        }

        return $classes;
    }

    /**
     * Check if given class targeted by locator.
     *
     * @param \ReflectionClass      $class
     * @param \ReflectionClass|null $target
     * @return bool
     */
    protected function isTargeted(\ReflectionClass $class, \ReflectionClass $target = null): bool
    {
        if (empty($target)) {
            return true;
        }

        if (!$target->isTrait()) {
            //Target is interface or class
            return $class->isSubclassOf($target) || $class->getName() == $target->getName();
        }

        //Checking using traits
        return in_array($target->getName(), $this->fetchTraits($class->getName()));
    }

    /**
     * Find the namespace in the tokens starting at a given key.
     *
     * @param  int  $key
     * @param  array  $tokens
     * @return string|null
     *
     */
    protected function getNamespace($key, array $tokens)
    {
        $namespace = null;
        $tokenCount = count($tokens);

        for ($i = $key; $i < $tokenCount; $i++) {
            if ($this->isPartOfNamespace($tokens[$i])) {
                $namespace .= $tokens[$i][1];
            } elseif ($tokens[$i] == ';') {
                return $namespace;
            }
        }
    }

    /**
     * Find the class in the tokens starting at a given key.
     *
     * @param  int  $key
     * @param  array  $tokens
     *
     * @return string|null
     */
    protected function getClass($key, array $tokens)
    {
        $class = null;
        $tokenCount = count($tokens);

        for ($i = $key; $i < $tokenCount; $i++) {
            if ($this->isPartOfClass($tokens[$i])) {
                $class .= $tokens[$i][1];
            } elseif ($this->isWhitespace($tokens[$i])) {
                return $class;
            }
        }
    }

    /**
     * Determine if the given token is a require or include keyword.
     *
     * @param  array|string  $token
     *
     * @return bool
     */
    protected function tokenIsIncludeOrRequire($token)
    {
        return is_array($token)
            && ($token[0] == T_INCLUDE || $token[0] == T_INCLUDE_ONCE || $token[0] == T_REQUIRE || $token[0] == T_REQUIRE_ONCE);
    }

    /**
     * Determine if the given token is a class or interface keyword.
     *
     * @param  array|string  $token
     *
     * @return bool
     */
    protected function tokenIsClassOrInterface($token)
    {
        return is_array($token) && ($token[0] == T_CLASS || $token[0] == T_INTERFACE || $token[0] == T_TRAIT);
    }

    /**
     * Determine if the given token is part of the namespace.
     *
     * @param  array|string  $token
     * @return bool
     */
    protected function isPartOfNamespace($token)
    {
        return is_array($token) && ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR);
    }

    /**
     * Determine if the given token is whitespace.
     *
     * @param  array|string  $token
     * @return bool
     */
    protected function isWhitespace($token)
    {
        return is_array($token) && $token[0] == T_WHITESPACE;
    }

    /**
     * Determine if the given token is a namespace keyword.
     *
     * @param  array|string  $token
     *
     * @return bool
     */
    protected function tokenIsNamespace($token)
    {
        return is_array($token) && $token[0] == T_NAMESPACE;
    }

    /**
     * Determine if the given token is part of the class.
     *
     * @param  array|string  $token
     * @return bool
     */
    protected function isPartOfClass($token)
    {
        return is_array($token) && $token[0] == T_STRING;
    }
}
