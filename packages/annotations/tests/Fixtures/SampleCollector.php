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

namespace Biurad\Annotations\Tests\Fixtures;

class SampleCollector
{
    private $collected = [];

    /**
     * @param \Reflector|string $handler
     */
    public function add(string $name, int $priority, $handler): void
    {
        $this->collected[$name] = [
            'handler' => $handler instanceof \Reflector ? $handler::class : $handler,
            'priority' => $priority,
        ];
    }

    public function getCollected(): \ArrayObject
    {
        return new \ArrayObject($this->collected);
    }
}
