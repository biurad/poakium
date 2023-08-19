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

use Biurad\Loader\Exceptions\FileLoadingException;
use Biurad\Loader\Exceptions\FileNotFoundException;

class FileLocator implements \IteratorAggregate, \Countable
{
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

    private int $maxDepth = -1;

    /**
     * @param array<int,string> $paths
     * @param array<int,string> $excludes
     */
    public function __construct(private array $paths = [], private array $excludes = [])
    {
        $this->excludes = \array_unique(\array_merge(self::IGNORE_VCS_FILES, $this->excludes));
    }

    /**
     * @return array<int,string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get pre-configured class locator.
     */
    public function getClassLocator(): ClassLocator
    {
        return new ClassLocator($this);
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
     * @return \Iterator|\SplFIleInfo[]
     */
    public function getIterator(): \Iterator
    {
        if (\count($this->paths) > 1) {
            $iterator = new \AppendIterator();

            foreach ($this->paths as $path) {
                $iterator->append($this->buildIterator($path));
            }

            return $iterator;
        }

        return $this->buildIterator($this->paths[0] ?? throw new FileLoadingException('No paths specified'));
    }

    /**
     * Extract the class name from the file at the given path.
     */
    public function findClass(string $path): ?string
    {
        return $this->getClassLocator()->findClass($path);
    }

    /**
     * Find all files in a list of directories.
     *
     * @param string $extension eg. (php, html|css|js, *)
     */
    public function findFiles(string $extension = 'php'): ?iterable
    {
        $files = '*' === $extension ? $this->getIterator() : new \RegexIterator($this->getIterator(), "/\.{$extension}$/");

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            yield from [$file->getPathname() => $file];
        }

        return null;
    }

    /**
     * Find all directories in a list of directories.
     */
    public function findDirectories(): ?iterable
    {
        foreach ($this->getIterator() as $path) {
            if ($path->isFile()) {
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
     * @param string|null $currentPath The current path
     * @param bool        $first       Whether to return the first occurrence or an array of filenames
     *
     * @return array|string The full path to the file or an array of file paths
     *
     * @throws \InvalidArgumentException If $name is empty
     * @throws FileNotFoundException     If a file is not found
     */
    public function locate(string $name, string $currentPath = null, bool $first = true)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        if ($this->isAbsolutePath($name)) {
            if (!\file_exists($name)) {
                throw new FileNotFoundException(\sprintf('The file "%s" does not exist.', $name));
            }

            return $name;
        }

        $paths = $this->paths;

        if (null !== $currentPath) {
            \array_unshift($paths, $currentPath);
        }

        $paths = \array_unique($paths);
        $filepaths = [];

        foreach ($paths as $path) {
            if (@\file_exists($file = $path.\DIRECTORY_SEPARATOR.$name)) {
                if (true === $first) {
                    return $file;
                }
                $filepaths[] = $file;
            }
        }

        if (!$filepaths) {
            throw new FileNotFoundException(\sprintf('The file "%s" does not exist (in: "%s").', $name, \implode('", "', $paths)));
        }

        return $filepaths;
    }

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * @param string|null $target Class, interface, trait or namespace\\. By default - null (all classes).
     *                            Parent (class) will also be included to classes list as one of
     *                            results.
     *
     * @return iterable<\ReflectionClass>|null
     *
     * @throws \ReflectionException
     */
    public function getClasses(string $target = null): ?iterable
    {
        return $this->getClassLocator()->getClasses($target);
    }

    /**
     * GlobFileLoader loads files from a glob pattern.
     *
     * @param bool $sort      Sort paths from $resource in ascending order
     * @param bool $exclusion If true, only paths will be returned else all files
     *
     * @return iterable<string, \SplFileInfo>
     */
    private function loadGlob(string $resource, bool $sort = false, bool $exclusion = false): iterable
    {
        $globBrace = \defined('GLOB_BRACE') ? \GLOB_BRACE : 0;
        $paths = [];

        if ($globBrace || !\str_contains($resource, '{')) {
            $paths = \glob($resource, \GLOB_NOSORT | $globBrace);
        } elseif (!\str_contains($resource, '\\') || !\preg_match('/\\\\[,{}]/', $resource)) {
            foreach ($this->expandGlob($resource) as $pattern) {
                $paths[] = \glob($pattern, \GLOB_NOSORT);
            }

            $paths = \array_merge(...$paths);
        }

        if ($sort) {
            \sort($paths);
        }

        foreach ($paths as $path) {
            if (\is_file($path)) {
                yield $path => new \SplFileInfo($path);
            }

            if (!\is_dir($path)) {
                continue;
            }

            if ($exclusion) {
                yield $path => new \SplFileInfo($path);
                continue;
            }

            $files = \iterator_to_array(new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator(
                        $path,
                        \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                    ),
                    fn (\SplFileInfo $file, $path) => '.' !== $file->getBasename()[0]
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ));
            \uasort($files, 'strnatcmp');

            foreach ($files as $path => $info) {
                if ($info->isFile()) {
                    yield $path => $info;
                }
            }
        }

        return $paths;
    }

    private function expandGlob(string $pattern): array
    {
        $segments = \preg_split('/\{([^{}]*+)\}/', $pattern, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $paths = [$segments[0]];
        $patterns = [];

        for ($i = 1; $i < \count($segments); $i += 2) {
            $patterns = [];

            foreach (\explode(',', $segments[$i]) as $s) {
                foreach ($paths as $p) {
                    $patterns[] = $p.$s.$segments[1 + $i];
                }
            }

            $paths = $patterns;
        }

        $j = 0;

        foreach ($patterns as $i => $p) {
            if (\str_contains($p, '{')) {
                $p = $this->expandGlob($p);
            }
            \array_splice($paths, $i + $j, 1, $p);
            $j += \count($p) - 1;
        }

        return $paths;
    }

    /**
     * Returns whether the file path is an absolute path.
     */
    private function isAbsolutePath(string $file): bool
    {
        if (
            (
                '/' === $file[0]
                || '\\' === $file[0]
            )
            || (
                \strlen($file) > 3 && \ctype_alpha($file[0])
                && ':' === $file[1]
                && (
                    '\\' === $file[2]
                    || '/' === $file[2]
                )
            )
            || null !== \parse_url($file, \PHP_URL_SCHEME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns per-path iterator.
     */
    private function buildIterator(string $path): \RecursiveIteratorIterator
    {
        $filter = function (\SplFileInfo $file): bool {
            if ('.' === $file->getFilename() || '..' === $file->getFilename()) {
                return false;
            }

            foreach ($this->excludes as $exclude) {
                if ($exclude === $file->getPathname() || \file_exists($file->getPath().'/'.\ltrim($exclude, '/'))) {
                    return false;
                }
            }

            return true;
        };

        $iterator = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new \RecursiveCallbackFilterIterator($iterator, $filter);
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $iterator->setMaxDepth($this->maxDepth);

        return $iterator;
    }
}
