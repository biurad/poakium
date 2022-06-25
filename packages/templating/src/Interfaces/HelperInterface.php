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

/**
 * HelperInterface is the interface all helpers must implement.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface HelperInterface
{
    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string;

    /**
     * Sets the default charset.
     */
    public function setCharset(string $charset): void;

    /**
     * Gets the default charset.
     *
     * @return string The default charset
     */
    public function getCharset(): string;
}
