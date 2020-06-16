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

use AppendIterator;
use BiuradPHP\Loader\Exceptions\FileNotFoundException;
use BiuradPHP\Loader\Exceptions\LoaderException;
use BiuradPHP\Loader\Interfaces\ClassInterface;
use BiuradPHP\Loader\Interfaces\FileLocatorInterface;
use CallbackFilterIterator;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use OuterIterator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileLocator implements ClassInterface, FileLocatorInterface, LoggerAwareInterface, IteratorAggregate, Countable
{
    use LoggerAwareTrait;

    public const IGNORE_VCS_FILES = [
        '.svn',
        '_svn',
        'CVS',
        '_darcs',
        '.arch-params',
        '.monotone',
        '.bzr',
        '.git',
        '.hg',
    ];

    private $paths = [];

    private $excludes = [];

    private $maxDepth = -1;

    /**
     * @param array $directories
     * @param array $excludes
     */
    public function __construct(array $directories = [], $excludes = [])
    {
        if (!empty($directories)) {
            $this->paths = $directories;
        }

        $this->setExcludes($excludes);
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Set Excluded paths
     *
     * @param array $paths
     */
    public function setExcludes(array $paths): void
    {
        \array_walk($paths, function (string $path): void {
            $this->excludes[] = function (RecursiveDirectoryIterator $file) use ($path): bool {
                return !$file->isDot()
                    && !\strpos(\strtr($file->getPathname(), '\\', '/'), $path);
            };
        });
    }

    /**
     * Get pre-configured class locator.
     *
     * @return ClassLocator
     */
    public function getClassLocator(): ClassLocator
    {
        $classLocator = new ClassLocator($this);

        if (null !== $this->logger) {
            $classLocator->setLogger($this->logger);
        }

        return $classLocator;
    }

    /**
     * Get the number of found files and/or directories.
     */
    public function count(): int
    {
        return \iterator_count($this->getIterator());
    }

    /**
     * Returns iterator.
     *
     * @return Iterator|SplFIleInfo[]
     */
    public function getIterator(): Iterator
    {
        if (!$this->paths) {
            throw new LoaderException('Call specify directory to search on constructor method.');
        }

        if (\count($this->paths) === 1) {
            return $this->buildIterator((string) $this->paths[0]);
        }

        $iterator = new AppendIterator();

        foreach ($this->paths as $path) {
            $iterator->append($this->buildIterator((string) $path));
        }

        return $iterator;
    }

    /**
     * Extract the class name from the file at the given path.
     *
     * @param string $path
     *
     * @return null|string
     */
    public function findClass(string $path): ?string
    {
        return $this->getClassLocator()->findClass($path);
    }

    /**
     * Fiind all files in a list of directories.
     *
     * @param array|string $extension
     *
     * @return null|iterable
     */
    public function findFiles(string $extension = 'php'): ?iterable
    {
        /** @var SplFileInfo $file */
        foreach ($this->getIterator() as $file) {
            if (('*' !== $extension && $file->getExtension() !== $extension) || !$file->isFile()) {
                continue;
            }

            yield from [$file->getPathname() => $file];
        }

        return null;
    }

    /**
     * Fiind all directories in a list of directories.
     *
     * @return null|iterable
     */
    public function findDirectories(): ?iterable
    {
        /** @var SplFileInfo $path */
        foreach ($this->getIterator() as $path) {
            if ($path->isFile() || ('.' === $path->getFilename() || '..' === $path->getFilename())) {
                continue;
            }

            yield from [$path->getPathname() => $path];
        }

        return null;
    }

    /**
     * Returns a full path for a given file name.
     *
     * @param string      $name        The file name to locate
     * @param null|string $currentPath The current path
     * @param bool        $first       Whether to return the first occurrence or an array of filenames
     *
     * @throws InvalidArgumentException         If $name is empty
     * @throws FileLocatorFileNotFoundException If a file is not found
     *
     * @return array|string The full path to the file or an array of file paths
     */
    public function locate(string $name, string $currentPath = null, bool $first = true)
    {
        if ('' === $name) {
            throw new InvalidArgumentException('An empty file name is not valid to be located.');
        }

        if ($this->isAbsolutePath($name)) {
            if (!\file_exists($name)) {
                throw new FileNotFoundException(\sprintf('The file "%s" does not exist.', $name), 0, null, [$name]);
            }

            return $name;
        }

        $paths = $this->paths;

        if (null !== $currentPath) {
            \array_unshift($paths, $currentPath);
        }

        $paths     = \array_unique($paths);
        $filepaths = [];

        foreach ($paths as $path) {
            if (@\file_exists($file = $path . \DIRECTORY_SEPARATOR . $name)) {
                if (true === $first) {
                    return $file;
                }
                $filepaths[] = $file;
            }
        }

        if (!$filepaths) {
            throw new FileNotFoundException(
                \sprintf(
                    'The file "%s" does not exist (in: "%s").',
                    $name,
                    \implode('", "', $paths)
                )
            );
        }

        return $filepaths;
    }

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * @param mixed $target Class, interface or trait parent. By default - null (all classes).
     *                      Parent (class) will also be included to classes list as one of
     *                      results.
     *
     * @throws ReflectionException
     *
     * @return null|iterable|ReflectionClass[]
     */
    public function getClasses($target = null): ?iterable
    {
        return $this->getClassLocator()->getClasses($target);
    }

    /**
     * Returns whether the file path is an absolute path.
     */
    private function isAbsolutePath(string $file): bool
    {
        if (
            (
                '/' === $file[0] ||
                '\\' === $file[0]
            ) ||
            (
                \strlen($file) > 3 && \ctype_alpha($file[0]) &&
                ':' === $file[1] &&
                (
                    '\\' === $file[2] ||
                    '/' === $file[2]
                )
            ) ||
            null !== \parse_url($file, \PHP_URL_SCHEME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns per-path iterator.
     */
    private function buildIterator(string $path): Iterator
    {
        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::FOLLOW_SYMLINKS);

        if ($this->excludes) {
            $iterator = new RecursiveCallbackFilterIterator(
                $iterator,
                function ($foo, $bar, RecursiveDirectoryIterator $file): bool {
                    if (!$file->isDot() && !$file->isFile()) {
                        foreach ($this->excludes as $filter) {
                            if (!$filter($file)) {
                                return false;
                            }
                        }
                    }

                    return true;
                }
            );
        }

        if ($this->maxDepth !== 0) {
            $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
            $iterator->setMaxDepth($this->maxDepth);
        }

        $iterator = new CallbackFilterIterator($iterator, function ($foo, $bar, Iterator $file): bool {
            while ($file instanceof OuterIterator) {
                $file = $file->getInnerIterator();
            }

            return true;
        });

        return $iterator;
    }
}
