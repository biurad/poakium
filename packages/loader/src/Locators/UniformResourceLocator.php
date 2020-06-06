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

namespace BiuradPHP\Loader\Locators;

use BadMethodCallException, RecursiveIteratorIterator;
use BiuradPHP\Loader\Files\RecursiveUniformResourceIterator;
use BiuradPHP\Loader\Files\UniformResourceIterator;
use BiuradPHP\Loader\Interfaces\ResourceLocatorInterface;
use InvalidArgumentException;
use Exception;
use RuntimeException;

/**
 * Implements Uniform Resource Location.
 *
 * Most of the methods in this file come from Grav CMS,
 * thanks to RocketTheme Team for such a useful class.
 *
 * @author RocketTheme
 * @license MIT
 * @license BSD-3-Clause
 *
 * @see http://webmozarts.com/2013/06/19/the-power-of-uniform-resource-location-in-php/
 */
class UniformResourceLocator implements ResourceLocatorInterface
{
    /**
     * @var string base URL for all the streams
     */
    public $base;

    /**
     * @var array[]
     */
    protected $schemes = [];

    /**
     * @var array
     */
    protected $cache = [];

    public function __construct($base = null)
    {
        // Normalize base path.
        $this->base = rtrim(str_replace('\\', '/', $base ?: getcwd()), '/');
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator($uri, $flags = null): UniformResourceIterator
    {
        return new UniformResourceIterator($uri, $flags, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecursiveIterator($uri, $flags = null): RecursiveUniformResourceIterator
    {
        return new RecursiveUniformResourceIterator($uri, $flags, $this);
    }

    /**
     * Reset locator by removing all the schemes.
     *
     * @return $this
     */
    public function reset()
    {
        $this->schemes = [];
        $this->cache = [];

        return $this;
    }

    /**
     * Reset a locator scheme.
     *
     * @param string $scheme The scheme to reset
     *
     * @return $this
     */
    public function resetScheme($scheme)
    {
        $this->schemes[$scheme] = [];
        $this->cache = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addPath($scheme, $prefix, $paths, $override = false, $force = true)
    {
        $list = [];
        foreach ((array) $paths as $path) {
            if (is_array($path)) {
                // Support stream lookup in ['theme', 'path/to'] format.
                if (count($path) !== 2 || !is_string($path[0]) || !is_string($path[1])) {
                    throw new BadMethodCallException('Invalid stream path given.');
                }
                $list[] = $path;
            } elseif (false !== strpos($path, '://')) {
                // Support stream lookup in 'theme://path/to' format.
                $stream = explode('://', $path, 2);
                $stream[1] = trim($stream[1], '/');

                $list[] = $stream;
            } else {
                // Normalize path.
                $path = rtrim(str_replace('\\', '/', $path), '/');
                if ($force || @file_exists("{$this->base}/{$path}") || @file_exists($path)) {
                    // Support for absolute and relative paths.
                    $list[] = $path;
                }
            }
        }

        if (isset($this->schemes[$scheme][$prefix])) {
            $paths = $this->schemes[$scheme][$prefix];
            if (!$override || $override == 1) {
                $list = $override ? array_merge($paths, $list) : array_merge($list, $paths);
            } else {
                $location = array_search($override, $paths, true) ?: count($paths);
                array_splice($paths, $location, 0, $list);
                $list = $paths;
            }
        }

        $this->schemes[$scheme][$prefix] = $list;

        // Sort in reverse order to get longer prefixes to be matched first.
        krsort($this->schemes[$scheme]);

        $this->cache = [];
    }

    /**
     * Return base directory.
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * Return true if scheme has been defined.
     *
     * @param string $name
     * @return bool
     */
    public function schemeExists($name): bool
    {
        return isset($this->schemes[$name]);
    }

    /**
     * Return defined schemes.
     */
    public function getSchemes(): array
    {
        return array_keys($this->schemes);
    }

    /**
     * Return all scheme lookup paths.
     *
     * @param string $scheme
     * @return array
     */
    public function getPaths($scheme = null): array
    {
        return !$scheme ? $this->schemes : (isset($this->schemes[$scheme]) ? $this->schemes[$scheme] : []);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke($uri)
    {
        if (!is_string($uri)) {
            throw new BadMethodCallException('Invalid parameter $uri.');
        }

        return $this->findCached($uri, false, true, false);
    }

    /**
     * Returns true if uri is resolvable by using locator.
     *
     * @param string $uri
     *
     * @return bool
     */
    public function isStream($uri)
    {
        try {
            list($scheme) = $this->normalize($uri, true, true);
        } catch (Exception $e) {
            return false;
        }

        return $this->schemeExists($scheme);
    }

    /**
     * Returns the canonicalized URI on success. The resulting path will have no '/./' or '/../' components.
     * Trailing delimiter `/` is kept.
     *
     * By default (if $throwException parameter is not set to true) returns false on failure.
     *
     * @param string $uri
     * @param bool   $throwException
     * @param bool   $splitStream
     *
     * @return string|array|bool
     *
     * @throws BadMethodCallException
     */
    public function normalize($uri, $throwException = false, $splitStream = false)
    {
        if (!is_string($uri)) {
            if ($throwException) {
                throw new BadMethodCallException('Invalid parameter $uri.');
            }

            return false;
        }

        $uri = preg_replace('|\\\|u', '/', $uri);
        $segments = explode('://', $uri, 2);
        $path = array_pop($segments);
        $scheme = array_pop($segments) ?: 'file';

        if ($path) {
            $path = preg_replace('|\\\|u', '/', $path);
            $parts = explode('/', $path);

            $list = [];
            foreach ($parts as $i => $part) {
                if ($part === '..') {
                    $part = array_pop($list);
                    if ($part === null || $part === '' || (!$list && strpos($part, ':'))) {
                        if ($throwException) {
                            throw new BadMethodCallException('Invalid parameter $uri.');
                        }

                        return false;
                    }
                } elseif (($i && $part === '') || $part === '.') {
                    continue;
                } else {
                    $list[] = $part;
                }
            }

            if (($l = end($parts)) === '' || $l === '.' || $l === '..') {
                $list[] = '';
            }

            $path = implode('/', $list);
        }

        return $splitStream ? [$scheme, $path] : ($scheme !== 'file' ? "{$scheme}://{$path}" : $path);
    }

    /**
     * {@inheritDoc}
     */
    public function findResource($uri, $absolute = true, $first = false): string
    {
        if (!is_string($uri)) {
            throw new BadMethodCallException('Invalid parameter $uri.');
        }

        return $this->findCached($uri, false, $absolute, $first);
    }

    /**
     * {@inheritDoc}
     */
    public function findResources($uri, $absolute = true, $all = false): array
    {
        if (!is_string($uri)) {
            throw new BadMethodCallException('Invalid parameter $uri.');
        }

        return $this->findCached($uri, true, $absolute, $all);
    }

    /**
     * {@inheritDoc}
     */
    public function mergeResources(array $uris, $absolute = true, $all = false): array
    {
        $uris = array_unique($uris);

        $lists = [[]];
        foreach ($uris as $uri) {
            $lists[] = $this->findResources($uri, $absolute, $all);
        }

        return array_merge(...$lists);
    }

    /**
     * Pre-fill cache by a stream.
     *
     * @param string $uri
     *
     * @return $this
     */
    public function fillCache($uri)
    {
        $cacheKey = $uri.'@cache';

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = true;

            $iterator = new RecursiveIteratorIterator($this->getRecursiveIterator($uri), RecursiveIteratorIterator::SELF_FIRST);

            /* @var UniformResourceIterator $uri */
            foreach ($iterator as $item) {
                $key = $item->getUrl().'@010';
                $this->cache[$key] = $item->getPathname();
            }
        }

        return $this;
    }

    /**
     * Reset locator cache.
     *
     * @param string $uri
     *
     * @return $this
     */
    public function clearCache($uri = null)
    {
        if ($uri) {
            $this->clearCached($uri, true, true, true);
            $this->clearCached($uri, true, true, false);
            $this->clearCached($uri, true, false, true);
            $this->clearCached($uri, true, false, false);
            $this->clearCached($uri, false, true, true);
            $this->clearCached($uri, false, true, false);
            $this->clearCached($uri, false, false, true);
            $this->clearCached($uri, false, false, false);
        } else {
            $this->cache = [];
        }

        return $this;
    }

    /**
     * @param string $uri
     * @param bool   $array
     * @param bool   $absolute
     * @param bool   $all
     *
     * @return array|string|bool
     *
     * @throws BadMethodCallException
     */
    protected function findCached($uri, $array, $absolute, $all)
    {
        // Local caching: make sure that the function gets only called at once for each file.
        $key = $uri.'@'.(int) $array.(int) $absolute.(int) $all;

        if (!isset($this->cache[$key])) {
            try {
                list($scheme, $file) = $this->normalize($uri, true, true);

                if (!$file && $scheme === 'file') {
                    $file = $this->base;
                }

                $this->cache[$key] = $this->find($scheme, $file, $array, $absolute, $all);
            } catch (BadMethodCallException $e) {
                $this->cache[$key] = $array ? [] : false;
            }
        }

        return $this->cache[$key];
    }

    protected function clearCached($uri, $array, $absolute, $all)
    {
        // Local caching: make sure that the function gets only called at once for each file.
        $key = $uri.'@'.(int) $array.(int) $absolute.(int) $all;

        unset($this->cache[$key]);
    }

    /**
     * @param string $scheme
     * @param string $file
     * @param bool   $array
     * @param bool   $absolute
     * @param bool   $all
     *
     * @throws InvalidArgumentException
     *
     * @return array|string|bool
     *
     * @internal
     */
    protected function find($scheme, $file, $array, $absolute, $all)
    {
        if (!isset($this->schemes[$scheme])) {
            throw new InvalidArgumentException("Invalid resource {$scheme}://");
        }

        $results = $array ? [] : false;
        foreach ($this->schemes[$scheme] as $prefix => $paths) {
            if ($prefix && strpos($file, $prefix) !== 0) {
                continue;
            }

            // Remove prefix from filename.
            $filename = '/'.trim(substr($file, strlen($prefix)), '\/');

            foreach ($paths as $path) {
                if (is_array($path)) {
                    // Handle scheme lookup.
                    $relPath = trim($path[1].$filename, '/');
                    $found = $this->find($path[0], $relPath, $array, $absolute, $all);
                    if ($found) {
                        if (!$array) {
                            return $found;
                        }
                        $results = array_merge($results, $found);
                    }
                } else {
                    // TODO: We could provide some extra information about the path to remove preg_match().
                    // Check absolute paths for both unix and windows
                    if (!$path || !preg_match('`^/|\w+:`', $path)) {
                        // Handle relative path lookup.
                        $relPath = trim($path.$filename, '/');
                        $fullPath = $this->base.'/'.$relPath;
                    } else {
                        // Handle absolute path lookup.
                        $fullPath = rtrim($path.$filename, '/');
                        if (!$absolute) {
                            throw new RuntimeException("UniformResourceLocator: Absolute stream path with relative lookup not allowed ({$prefix})", 500);
                        }
                    }

                    if ($all || file_exists($fullPath)) {
                        $current = $absolute ? $fullPath : $relPath;
                        if (!$array) {
                            return $current;
                        }
                        $results[] = $current;
                    }
                }
            }
        }

        return $results;
    }
}
