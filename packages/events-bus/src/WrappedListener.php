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

namespace Biurad\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class WrappedListener
{
    /** @var mixed */
    private $listener;

    /** @var string */
    private $name;

    /** @var bool */
    private $called;

    /** @var bool */
    private $stoppedPropagation;

    /** @var null|EventDispatcherInterface */
    private $dispatcher;

    /** @var string */
    private $duration;

    /** @var null|string */
    private $pretty;

    /** @var null|int */
    private $priority;

    /**
     * @param mixed                    $listener
     * @param null|string              $name
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct($listener, ?string $name, EventDispatcherInterface $dispatcher = null)
    {
        if (\is_callable($listener)) {
            $listener = \Closure::fromCallable($listener);
        }

        $this->listener           = $listener;
        $this->dispatcher         = $dispatcher;
        $this->called             = false;
        $this->stoppedPropagation = false;

        $this->trackEventListener($this->listener);

        if (null !== $name) {
            $this->name = $name;
        }
    }

    public function __invoke(Event $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $dispatcher = $this->dispatcher ?? $dispatcher;

        $this->called   = true;
        $this->priority = $dispatcher->getListenerPriority($eventName, $this->listener);
        $timeStart      = \microtime(true);

        try {
            ($this->listener)($event, $eventName, $dispatcher);
        } catch (\TypeError $e) {
            if (!$dispatcher instanceof TraceableEventDispatcher) {
                throw $e;
            }

            if ($this->isLazyDispatcher($dispatcher)) {
                $dispatcher->getResolver()->call($this->listener, [
                    \get_class($event)               => $event,
                    'eventName'                      => $eventName,
                    \get_class($dispatcher)          => $dispatcher,
                ]);
            }
        }

        $this->duration = \number_format((\microtime(true) - $timeStart) * 1000, 2) . 'ms';

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            $this->stoppedPropagation = true;
        }
    }

    /**
     * @return mixed
     */
    public function getWrappedListener()
    {
        return $this->listener;
    }

    public function wasCalled(): bool
    {
        return $this->called;
    }

    public function stoppedPropagation(): bool
    {
        return $this->stoppedPropagation;
    }

    public function getPretty(): ?string
    {
        return $this->pretty;
    }

    /**
     * @param string $eventName
     *
     * @return array<string,mixed>
     */
    public function getInfo(string $eventName): array
    {
        $priority = $this->priority;

        if (null === $priority && null !== $this->dispatcher) {
            $priority = $this->dispatcher->getListenerPriority($eventName, $this->listener);
        }

        return [
            'event'      => $eventName,
            'priority'   => $priority,
            'duration'   => $this->duration,
            'pretty'     => $this->pretty,
        ];
    }

    private function isLazyDispatcher(EventDispatcherInterface $dispatcher): bool
    {
        if ($dispatcher instanceof TraceableEventDispatcher) {
            $dispatcher = $dispatcher->getDispatcher();
        }

        return $dispatcher instanceof LazyEventDispatcher;
    }

    /**
     * @param mixed $listener
     */
    private function trackEventListener($listener): void
    {
        if (\is_array($listener)) {
            $this->name   = \is_object($listener[0]) ? get_debug_type($listener[0]) : $listener[0];
            $this->pretty = $this->name . '::' . $listener[1];

            return;
        }

        if ($listener instanceof \Closure) {
            $r = new \ReflectionFunction($listener);

            if (false !== \strpos($r->name, '{closure}')) {
                $this->pretty = $this->name = 'closure';
            } elseif (null !== $class = $r->getClosureScopeClass()) {
                $this->name   = $class->name;
                $this->pretty = $this->name . '::' . $r->name;
            } else {
                $this->pretty = $this->name = $r->name;
            }

            return;
        }

        $this->pretty = \is_string($listener) ? $this->name = $listener : null;
    }
}
