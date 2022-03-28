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

interface StorageInterface
{
    /**
     * Add a location to stored views.
     *
     * @throws LoaderException if add location is not allowed
     */
    public function addLocation(string $location): void;

    /**
     * Loads the fully qualified template from storage provided.
     *
     * @param array<int,string> $namespaces If empty, template will be loaded from default paths
     *
     * @return string|null null if the template cannot be loaded, a string of file or content otherwise
     */
    public function load(string $template, array $namespaces): ?string;
}
