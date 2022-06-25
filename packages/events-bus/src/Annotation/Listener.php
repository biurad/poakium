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

namespace Biurad\Events\Annotation;

/**
 *  Annotation class for @Listener().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Listener
{
    /** @var string */
    private $event;

    /** @var int */
    private $priority;

    /**
     * @param @param array<string,mixed>|string $data
     * @param string $event
     * @param int $priority
     */
    public function __construct($data = null, string $event = null, int $priority = 0)
    {
        if (is_array($data) && isset($data['value'])) {
            $data['event'] = $data['value'];
            unset($data['value']);
        } elseif (\is_string($data)) {
            $data = ['event' => $data];
        }

        $this->event = $data['event'] ?? $event;
        $this->priority = $data['priority'] ?? $priority;

        if (empty($this->event) || !\is_string($this->event)) {
            throw new \InvalidArgumentException(\sprintf(
                '@Listener.event must %s.',
                empty($this->event) ? 'be not an empty string' : 'contain only a string'
            ));
        }

        if (!is_integer($this->priority)) {
            throw new \InvalidArgumentException('@Listener.priority must contain only an integer');
        }
    }

    /**
     * Get the event's priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the event listener
     *
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }
}
