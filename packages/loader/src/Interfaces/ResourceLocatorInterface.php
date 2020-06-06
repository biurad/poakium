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

namespace BiuradPHP\Loader\Interfaces;

use BadMethodCallException;
use BiuradPHP\Loader\Files\RecursiveUniformResourceIterator;
use BiuradPHP\Loader\Files\UniformResourceIterator;

interface ResourceLocatorInterface
{
    /**
     * Alias for findResource().
     *
     * @param $uri
     *
     * @return string|bool
     */
    public function __invoke($uri);

    /**
     * Add new paths to the scheme.
     *
     * @param string       $scheme
     * @param string       $prefix
     * @param string|array $paths
     * @param bool|string  $override True to add path as override, string
     * @param bool         $force    true to add paths even if them do not exist
     *
     * @throws BadMethodCallException
     */
    public function addPath($scheme, $prefix, $paths, $override = false, $force = true);

    /**
     * Return base directory.
     */
    public function getBase(): string;

    /**
     * Returns true if uri is resolvable by using locator.
     *
     * @param string $uri
     *
     * @return bool
     */
    public function isStream($uri);

    /**
     * Find highest priority instance from a resource.
     *
     * @param string $uri      input URI to be searched
     * @param bool   $absolute whether to return absolute path
     * @param bool   $first    whether to return first path even if it doesn't exist
     *
     * @throws BadMethodCallException
     */
    public function findResource($uri, $absolute = true, $first = false);

    /**
     * Find all instances from a resource.
     *
     * @param string $uri input URI to be searched
     * @param bool $absolute whether to return absolute path
     * @param bool $all whether to return all paths even if they don't exist
     *
     * @return array
     * @throws BadMethodCallException
     */
    public function findResources($uri, $absolute = true, $all = false): array;

    /**
     * Find all instances from a list of resources.
     *
     * @param array $uris input URIs to be searched
     * @param bool $absolute whether to return absolute path
     * @param bool $all whether to return all paths even if they don't exist
     *
     * @return array
     * @throws BadMethodCallException
     */
    public function mergeResources(array $uris, $absolute = true, $all = false): array;

    /**
     * Return iterator for the resource URI.
     *
     * @param string $uri
     * @param int $flags see constants from FilesystemIterator class
     *
     * @return UniformResourceIterator
     */
    public function getIterator($uri, $flags = null): UniformResourceIterator;

    /**
     * Return recursive iterator for the resource URI.
     *
     * @param string $uri
     * @param int $flags see constants from FilesystemIterator class
     *
     * @return RecursiveUniformResourceIterator
     */
    public function getRecursiveIterator($uri, $flags = null): RecursiveUniformResourceIterator;
}
