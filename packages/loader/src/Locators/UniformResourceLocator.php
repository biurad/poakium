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

use Biurad\Loader\Files\RecursiveUniformResourceIterator;
use Biurad\Loader\Files\UniformResourceIterator;

/**
 * Implements Uniform Resource Location.
 *
 * This was first implemented by Grav CMS.
 * thanks to RocketTheme Team. This code has been modified for performance.
 *
 * @author RocketTheme
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class UniformResourceLocator
{
    public const FLAG = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS;

    /** @var string base URL for all the streams */
    public string $base;

    /** @var array<string,array<int,string>|string> */
    protected $schemes = [];

    /** @var array<string,array<int,string>|string> */
    protected array $cache = [];

    public function __construct(string $base = null)
    {
        // Normalize base path.
        $this->base = \rtrim(\str_replace('\\', '/', $base ?: \getcwd()), '/');
    }

    /**
     * Return iterator for the resource URI.
     *
     * @param int $flags see constants from FilesystemIterator class
     */
    public function getIterator(string $uri, int $flags = self::FLAG): UniformResourceIterator
    {
        return new UniformResourceIterator($uri, $flags, $this);
    }

    /**
     * Return recursive iterator for the resource URI.
     *
     * @param int $flags see constants from FilesystemIterator class
     */
    public function getRecursiveIterator(string $uri, int $flags = self::FLAG): RecursiveUniformResourceIterator
    {
        return new RecursiveUniformResourceIterator($uri, $flags, $this);
    }

    /**
     * Reset locator by removing all the schemes.
     */
    public function reset(): self
    {
        $this->schemes = $this->cache = [];

        return $this;
    }

    /**
     * Add new paths to the scheme.
     *
     * @param array<int,string>|string $paths
     * @param bool                     $force true to add paths even if they do not exist
     *
     * @throws \TypeError When $paths is expected to be an array of strings
     */
    public function addPath(string $scheme, array|string $paths, bool $force = true): void
    {
        $list = [];

        foreach (\is_string($paths) ? [$paths] : $paths as $i => $path) {
            if (!\is_string($path)) {
                throw new \TypeError(\sprintf('addPath(): Argument #2 ($paths) must be of type string for index "%s"', $i));
            }

            if (!\str_contains($path, '://')) {
                // Normalize path.
                $path = \rtrim(\str_replace('\\', '/', $path), '/');

                if ($force || \file_exists("{$this->base}/{$path}") || \file_exists($path)) {
                    // Support for absolute and relative paths.
                    $list[] = $path;
                }
            } else {
                $list[] = $path; // Support stream lookup in 'theme://path/to' format.
            }
        }

        $this->schemes[$scheme] = $list;
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
        return \array_keys($this->schemes);
    }

    /**
     * Return all scheme lookup paths.
     */
    public function getPaths(string $scheme = null): array
    {
        return null === $scheme ? $this->schemes : $this->schemes[$scheme] ?? [];
    }

    /**
     * Returns true if uri is resolvable by using locator.
     */
    public function isStream(string $uri): bool
    {
        try {
            list($scheme) = $this->normalize($uri, true, true);
        } catch (\Exception $e) {
            return false;
        }

        return $this->schemeExists($scheme);
    }

    /**
     * Returns the canonicalized URI on success. The resulting path will have no '/./' or '/../' components.
     * Trailing delimiter `/` is kept.
     *
     * By default (if $throwException parameter is not set to true) returns null on failure.
     *
     * @return array|string|null
     *
     * @throws \UnexpectedValueException
     */
    public function normalize(string $uri, bool $throwException = false, bool $splitStream = false)
    {
        $scheme = null;
        $list = [];
        $uri = \preg_replace('|\\\|u', '/', $uri);

        if (\str_contains($uri, '://')) {
            $segments = \explode('://', $uri, 2);
            $scheme = $segments[0];

            if (!isset($segments[1])) {
                return $throwException ? throw new \UnexpectedValueException(\sprintf('Invalid URI: %s', $uri)) : null;
            }

            $uri = $segments[1];
        }

        foreach (\explode('/', $uri) as $i => $part) {
            if ('..' === $part) {
                $part = \array_pop($list);

                // TODO: Improve this base to support eg. app://../example.txt
                if (empty($part) || (!$list && \strpos($part, ':'))) {
                    return $throwException ? throw new \UnexpectedValueException(\sprintf('Invalid URI: %s', $uri)) : null;
                }
            } elseif (($i && '' === $part) || '.' === $part) {
                continue;
            } else {
                $list[] = $part;
            }
        }

        if ('' === $part || '.' === $part || '..' === $part) {
            $list[] = '';
        }

        $path = \implode('/', $list);

        return $splitStream ? [$scheme, $path] : (null !== $scheme ? "{$scheme}://{$path}" : $path);
    }

    /**
     * Find highest priority instance from a resource. (A.K.A first path).
     *
     * @param string $uri      input URI to be searched (eg. app://config/example.txt)
     * @param bool   $absolute whether to return absolute path
     *
     * @throws \UnexpectedValueException
     */
    public function findResource(string $uri, bool $absolute = true): ?string
    {
        return $this->findCached($uri, false, $absolute);
    }

    /**
     * Find all instances from a resource.
     *
     * @param string $uri      input URI to be searched
     * @param bool   $absolute whether to return absolute path
     *
     * @return array<int,string>
     *
     * @throws \UnexpectedValueException
     */
    public function findResources(string $uri, bool $absolute = true): array
    {
        return $this->findCached($uri, true, $absolute) ?? [];
    }

    /**
     * Find all instances from a list of resources.
     *
     * @param array<int,string> $uris     input URIs to be searched
     * @param bool              $absolute whether to return absolute path
     *
     * @throws \UnexpectedValueException
     */
    public function mergeResources(array $uris, bool $absolute = true): array
    {
        $lists = [[]];

        foreach (\array_unique($uris) as $uri) {
            $lists[] = $this->findResources($uri, $absolute);
        }

        return \array_merge(...$lists);
    }

    /**
     * Reset locator cache.
     *
     * @param string|null $uri Can be (eg. app://config/example.txt or app://config or app://).
     */
    public function clearCache(string $uri = null): void
    {
        if (null === $uri) {
            $this->cache = [];
            return;
        }

        if (isset($this->cache[$uri])) {
            unset($this->cache[$uri]);
            return;
        }

        foreach ($this->cache as $key => $value) {
            if (\str_starts_with($key, $uri)) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * @param bool $array    whether to return array or not
     * @param bool $absolute whether to return absolute path
     *
     * @return array<int,string>|string|null
     *
     * @throws \UnexpectedValueException
     */
    private function findCached(string $uri, bool $array, bool $absolute)
    {
        $key = '@'.(int) $array.(int) $absolute;

        if (!isset($this->cache[$uri][$key])) {
            try {
                [$scheme, $file] = $this->normalize($uri, true, true);
                $this->cache[$uri][$key] = $this->find($scheme ?? '', $file, $array, $absolute);
            } catch (\UnexpectedValueException $e) {
                throw new \UnexpectedValueException(\sprintf('Invalid URI: %s', $uri), 0, $e);
            }
        }

        return $this->cache[$uri][$key];
    }

    /**
     * @return array<int,string>|string|null
     *
     * @throws \UnexpectedValueException
     *
     * @internal
     */
    private function find(string $scheme, string $file, bool $array, bool $absolute)
    {
        if (!isset($this->schemes[$scheme])) {
            if (empty($scheme) && (\file_exists($file) || \file_exists($fullPath = "{$this->base}/{$file}"))) {
                $path = \preg_replace('|\\\|u', '/', $fullPath ?? $file);
                return $array ? [$path] : $path;
            }

            throw new \UnexpectedValueException(\sprintf("Invalid scheme: '%s' for URI: %s", $scheme, $file));
        }

        $results = []; // The list of found paths

        foreach ($this->schemes[$scheme] as $path) {
            if (\str_contains($path, '://')) {
                [$s, $u] = \explode('://', $path, 2);

                if (empty($path = $this->find($s, $u.'/'.$file, false, $absolute))) {
                    continue;
                }

                if ($array) {
                    $results[] = $path;
                    continue;
                }

                return $path;
            }

            $path = $this->normalize($path.($file ? "/{$file}" : ''), true);

            if (!\file_exists($fullPath = $this->base.'/'.$path)) {
                continue;
            }

            if (!$array) {
                return $absolute ? $fullPath : $path;
            }

            $results[] = $absolute ? $fullPath : $path;
        }

        return !empty($results) ? $results : null;
    }
}
