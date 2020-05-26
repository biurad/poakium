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
use BiuradPHP\Loader\Interfaces\ClassInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

class FileLoader implements ClassInterface, LoggerAwareInterface, \IteratorAggregate, \Countable
{
    use LoggerAwareTrait;

    public const IGNORE_VCS_FILES = ['.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg'];

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
        array_walk($paths, function (string $path) {
            $this->excludes[] = function (RecursiveDirectoryIterator $file) use ($path): bool {
                return !$file->isDot()
                    && !strpos(strtr($file->getPathname(), '\\', '/'), $path);
            };
        });
    }

    /**
     * Get pre-configured class locator.
     *
     * @param array $directories
     * @param array $exclude
     * @return ClassLoader
     */
    public function getClassLocator(): ClassLoader {
        $classLocator = new ClassLoader($this);
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
		return iterator_count($this->getIterator());
	}


	/**
	 * Returns iterator.
	 */
	public function getIterator(): \Iterator
	{
		if (!$this->paths) {
			throw new LoaderException('Call specify directory to search on constructor method.');

		} elseif (count($this->paths) === 1) {
			return $this->buildIterator((string) $this->paths[0]);

		} else {
			$iterator = new \AppendIterator();
			foreach ($this->paths as $path) {
				$iterator->append($this->buildIterator((string) $path));
			}
			return $iterator;
		}
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
        return $this->getClassLocator()->findClass($path);
    }

    /**
     * Fiind all files in a list of directories.
     *
     * @param string $extension
     *
     * @return array
     */
    public function findFiles(string $extension = '.php'): array
    {
        $classes = [];
        foreach ($this->getIterator() as $file) {
            if (('*' !== $extension && $file->getBasename($extension) === $file->getBasename()) || $file->isFile()) {
                continue;
            }

            $classes[] = $file->getPathname();
        }

        return $classes;
    }

    /**
     * Fiind all directories in a list of directories.
     *
     * @return array
     */
    public function findDirectories(): array
    {
        $directories = [];
        foreach ($this->getIterator() as $path) {
            if ($path->isFile() || ('.' === $path->getFilename() || '..' === $path->getFilename())) {
                continue;
            }

            $directories[] = $path->getPathname();
        }

        return $directories;
    }

    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * @param mixed $target Class, interface or trait parent. By default - null (all classes).
     *                       Parent (class) will also be included to classes list as one of
     *                       results.
     *
     * @return ReflectionClass[]
     * @throws ReflectionException
     */
    public function getClasses($target = null): array
    {
        return $this->getClassLocator()->getClasses($target);
    }

    /**
	 * Returns per-path iterator.
	 */
	private function buildIterator(string $path): \Iterator
	{
		$iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::FOLLOW_SYMLINKS);

		if ($this->excludes) {
			$iterator = new \RecursiveCallbackFilterIterator($iterator, function ($foo, $bar, RecursiveDirectoryIterator $file): bool {
				if (!$file->isDot() && !$file->isFile()) {
					foreach ($this->excludes as $filter) {
						if (!$filter($file)) {
							return false;
						}
					}
                }

				return true;
			});
        }

		if ($this->maxDepth !== 0) {
			$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
			$iterator->setMaxDepth($this->maxDepth);
		}

		$iterator = new \CallbackFilterIterator($iterator, function ($foo, $bar, \Iterator $file): bool {
			while ($file instanceof \OuterIterator) {
				$file = $file->getInnerIterator();
            }

			return true;
		});

		return $iterator;
    }
}
