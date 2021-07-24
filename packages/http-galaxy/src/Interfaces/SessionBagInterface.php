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

namespace Biurad\Http\Interfaces;

/**
 * Session Bag store.
 *
 * @author Drak <drak@zikula.org>
 */
interface SessionBagInterface
{
    /**
     * Gets this bag's name.
     */
    public function getName(): string;

    /**
     * Initializes the Bag.
     */
    public function initialize(array &$array);

    /**
     * Gets the storage key for this bag.
     */
    public function getStorageKey(): string;

    /**
     * Clears out data from bag.
     *
     * @return mixed Whatever data was contained
     */
    public function clear();
}
