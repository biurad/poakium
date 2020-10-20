<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\UI\Interfaces;

use Biurad\UI\Exceptions\LoaderException;
use Biurad\UI\Source;

interface LoaderInterface
{
    // Namespace/viewName separator.
    public const NS_SEPARATOR = '::';

    /**
     * Lock loader to specific file extension.
     *
     * @param string[] $extensions
     *
     * @return LoaderInterface
     */
    public function withExtensions(array $extensions): LoaderInterface;

    /**
     * Add a namespace resource loading to storage.
     * This is useful if working on a bundle or module website type.
     *
     * @param string          $namespace
     * @param string|string[] $hints     of paths or contents
     */
    public function addNamespace(string $namespace, $hints): void;

    /**
     * Check if given view path has associated view in a loader.
     * Path might include namespace prefix or extension.
     *
     * @param string $view
     *
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * Get source for given name. Path might include namespace prefix or extension.
     *
     * @param string $view
     *
     * @throws LoaderException if unable to load view
     *
     * @return Source
     */
    public function find(string $view): Source;
}
