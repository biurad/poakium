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

namespace Biurad\Monorepo;

/**
 * A interface to define the exist index of a worker.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface PriorityInterface
{
    public function getPriority(): int;
}
