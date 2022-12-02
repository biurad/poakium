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

namespace Biurad\UI\Helper;

use Biurad\UI\Interfaces\HelperInterface;

/**
 * Helper is the base class for all helper classes.
 *
 * Most of the time, a Helper is an adapter around an existing
 * class that exposes a read-only interface for templates.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class Helper implements HelperInterface
{
    /** @var string */
    protected $charset = 'UTF-8';

    /**
     * {@inheritdoc}
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset(): string
    {
        return $this->charset;
    }
}
