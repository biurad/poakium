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

namespace BiuradPHP\Loader;

use BiuradPHP\Loader\Interfaces\AliasTypeInterface;
use BiuradPHP\Loader\Interfaces\LoaderInterface;
use BiuradPHP\Loader\Locators\AliasLocator;
use BiuradPHP\Loader\Locators\ConfigLocator;
use BiuradPHP\Loader\Locators\FileLocator;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * This loader acts as an array of loadable callbles/resources - each having
 * a chance to load a given resource.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LoaderManager implements LoaderInterface
{
    /**
     * @var array<string,callable> An array of LoaderInterface objects
     */
    private $loaders = [];

    /**
     * {@inheritdoc}
     */
    public function load($resource, string $type = null)
    {
        if (!\method_exists($this, ($loadingMethod = 'load' . \ucfirst(\strtolower($type))))) {
            return $this->{$loadingMethod}($resource);
        }

        foreach ($this->loaders as $supports => $loader) {
            if ($type === $supports) {
                return $loader($resource);
            }
        }

        return false;
    }

    /**
     * Add a LoaderInterface instance to $this class.
     *
     * @param callable $loader
     */
    public function addLoader(string $type, callable $loader): void
    {
        $this->loaders[$type] = $loader;
    }

    /**
     * AliasLoader loads namesapces and class aliases.
     *
     * @param string $resource
     *
     * @return AliasLocator|bool
     */
    private function loadAlias($resource)
    {
        if (!\is_array($resource) || !$resource instanceof AliasTypeInterface) {
            return false;
        }

        return new AliasLocator(\is_array($resource) ? $resource : [$resource]);
    }

    /**
     * ConfigLoader loads data from a file.
     *
     * @param string $resource
     *
     * @return bool|iterable
     */
    private function loadConfig(string $resource)
    {
        if (!\is_file($resource)) {
            return false;
        }

        return yield from (new ConfigLocator())->loadFile($resource);
    }

    /**
     * DirectoryLoader is a recursive loader to go through directories.
     *
     * @param string $resource
     *
     * @return bool|iterable
     */
    private function loadDirectory(string $resource): bool
    {
        if (!(\is_dir($resource) || '/' === \substr($resource, -1))) {
            return false;
        }

        return (new FileLocator((array) $resource))->findDirectories();
    }

    /**
     * FileLoader is a recursive loader to go through file directory.
     *
     * @param string $resource
     *
     * @return array|bool|string
     */
    private function loadFile(string $resource): bool
    {
        if (!\is_file($resource)) {
            return false;
        }

        return (new FileLocator())->locate($resource, \dirname($resource));
    }

    /**
     * GlobFileLoader loads files from a glob pattern.
     *
     * @param string $resource
     *
     * @return null|iterable
     */
    private function loadGlob(string $resource): ?iterable
    {
        $globBrace = \defined('GLOB_BRACE') ? \GLOB_BRACE : 0;
        $paths     = null;

        if ($globBrace || false === \strpos($resource, '{')) {
            $paths = \glob($resource, \GLOB_NOSORT | $globBrace);
        } elseif (false === \strpos($resource, '\\') || !\preg_match('/\\\\[,{}]/', $resource)) {
            foreach ($this->expandGlob($resource) as $pattern) {
                $paths[] = \glob($pattern, \GLOB_NOSORT);
            }

            $paths = \array_merge(...$paths);
        }

        if (null !== $paths) {
            \sort($paths);

            foreach ($paths as $path) {
                if (\is_file($path)) {
                    yield $path => new SplFileInfo($path);
                }

                if (!\is_dir($path)) {
                    continue;
                }

                if ($this->forExclusion) {
                    yield $path => new SplFileInfo($path);

                    continue;
                }

                $files = \iterator_to_array(new RecursiveIteratorIterator(
                    new RecursiveCallbackFilterIterator(
                        new RecursiveDirectoryIterator(
                            $path,
                            FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                        ),
                        function (SplFileInfo $file, $path) {
                            return '.' !== $file->getBasename()[0];
                        }
                    ),
                    RecursiveIteratorIterator::LEAVES_ONLY
                ));
                \uasort($files, 'strnatcmp');

                foreach ($files as $path => $info) {
                    if ($info->isFile()) {
                        yield $path => $info;
                    }
                }
            }
        }

        return $paths;
    }

    private function expandGlob(string $pattern): array
    {
        $segments = \preg_split('/\{([^{}]*+)\}/', $pattern, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $paths    = [$segments[0]];
        $patterns = [];

        for ($i = 1; $i < \count($segments); $i += 2) {
            $patterns = [];

            foreach (\explode(',', $segments[$i]) as $s) {
                foreach ($paths as $p) {
                    $patterns[] = $p . $s . $segments[1 + $i];
                }
            }

            $paths = $patterns;
        }

        $j = 0;

        foreach ($patterns as $i => $p) {
            if (false !== \strpos($p, '{')) {
                $p = $this->expandGlob($p);
            }
            \array_splice($paths, $i + $j, 1, $p);
            $j += \count($p) - 1;
        }

        return $paths;
    }
}
