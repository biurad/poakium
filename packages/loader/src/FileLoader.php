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

namespace BiuradPHP\Loader;

use BiuradPHP\Loader\Interfaces\ClassInterface;

class FileLoader implements ClassInterface
{
    private $dirs = [], $excludes = [];

    /**
     * @param array $directories
     * @param array $excludes
     */
    public function __construct(array $directories = [], $excludes = [])
    {
        if (!empty($directories)) {
            $this->dirs = $directories;
        }
        $this->excludes = $excludes;
    }

    public function getDirs(): array
    {
        return $this->dirs;
    }

    /**
     * Add a location to the file loader.
     *
     * @param array $directories
     */
    public function addLocation(array $directories): void
    {
        $this->dirs = array_merge($this->dirs, $directories);
    }

    /**
     * Set the value of excludes
     *
     * @return  self
     */
    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    /**
     * @param $class
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function findNamspaceForClass($class): string
    {
        $class = new \ReflectionClass($class);

        $namespace = $class->getNamespaceName();

        return $namespace;
    }

    /**
     * Fiind all files in a list of directories.
     *
     * @param array  $dirs
     * @param string $extension
     *
     * @return array
     */
    public function findFiles(array $dirs = [], $extension = '.php'): array
    {
        $classes = [];
        $this->dirs = !empty($dirs) ? $this->dirs = $dirs : $this->dirs;

        foreach ($this->dirs as $prefix => $dir) {

            if (!is_dir($dir)) {
                continue;
            }
            
            /** @var \RecursiveIteratorIterator|\SplFileInfo[] $iterator */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach (@$iterator as $file) {
                if (($fileName = $file->getBasename('.'.$extension)) == $file->getBasename()) {
                    continue;
                }

                $classes[] = $file->getRealPath();
            }
        }

        return $classes;
    }

    /**
     * Find all the class and interface names in a given directory.
     *
     * @param  array $directories
     *
     * @return array
     */
    public function findClasses(array $directories = [], array $excludes = []): array
    {
        $this->excludes = !empty($excludes) ? $this->excludes = $excludes : $this->excludes;

        $found = [];
        foreach ($this->findFiles($directories, 'php') as $file) {
            // Remove Excludes
            foreach ($this->excludes as $exclude) {
                $exclude = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $exclude);

                if (mb_strpos(@$file, $exclude)) {
                    unset($file);
                    //continue;
                }
            }

            $found[] = $this->findClass(@$file);
        }

        return $found;
    }

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * @param mixed $target  Class, interface or trait parent. By default - null (all classes).
     *                       Parent (class) will also be included to classes list as one of
     *                       results.
     *
     * @return \ReflectionClass[]
     */
    public function getClasses($target = null): array
    {
        if (!empty($target) && (is_object($target) || is_string($target))) {
            $target = new \ReflectionClass($target);
        }

        $result = [];
        foreach ($this->availableClasses() as $class) {
            //In some cases reflection can thrown an exception if class invalid or can not be loaded,
            //we are going to handle such exception and convert it soft exception
            $reflection = new \ReflectionClass($class);

            if (!$this->isTargeted($reflection, $target) || $reflection->isInterface()) {
                continue;
            }

            $result[$reflection->getName()] = $reflection;
        }

        return $result;
    }

    /**
     * Extract the class name from the file at the given path.
     *
     * @param  string|null  $path
     *
     * @return string|null
     */
    public function findClass(?string $path)
    {
        $namespace = null;

        if (null !== $path) {
            $tokens = token_get_all(file_get_contents($path));

            foreach ($tokens as $key => $token) {
                if ($this->tokenIsNamespace($token)) {
                    $namespace = $this->getNamespace($key + 2, $tokens);
                } elseif ($this->tokenIsClassOrInterface($token)) {
                    return ltrim($namespace.'\\'.$this->getClass($key + 2, $tokens), '\\');
                }
            }
        }
    }

    /**
     * Available file reflections. Generator.
     *
     * @return \ReflectionClass[]|\Generator
     */
    protected function availableReflections(): \Generator
    {
        foreach ($this->findClasses($this->dirs) as $class) {
            if (in_array($class, $this->excludes)) {
                continue;
            }

            yield $class;
        }
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
     * Determine if the given token is a namespace keyword.
     *
     * @param  array|string  $token
     * @return bool
     */
    protected function tokenIsNamespace($token)
    {
        return is_array($token) && $token[0] == T_NAMESPACE;
    }

    /**
     * Determine if the given token is a class or interface keyword.
     *
     * @param  array|string  $token
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
     * Determine if the given token is part of the class.
     *
     * @param  array|string  $token
     * @return bool
     */
    protected function isPartOfClass($token)
    {
        return is_array($token) && $token[0] == T_STRING;
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
}
