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

namespace Biurad\Git;

/**
 * Git Remote Object.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Remote implements \Stringable
{
    private string $push;

    public function __construct(private string $name, private string $fetch, string $push = null)
    {
        $this->push = $push ?? $fetch;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getFetchUrl(): string
    {
        return $this->fetch;
    }

    public function getPushUrl(): string
    {
        return $this->push;
    }
}
