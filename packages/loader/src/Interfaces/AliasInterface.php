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

namespace BiuradPHP\Loader\Interfaces;

interface AliasInterface
{
    public function addAliasType(AliasTypeInterface $alias): AliasInterface;

    /**
     * Add an alias to the loader.
     *
     * @param string $classOrNamespace
     * @param string $alias
     */
    public function addAlias(string $classOrNamespace, string $alias): AliasInterface;

    /**
     * Register the loader on the auto-loader stack.
     */
    public function register(): void;
}
