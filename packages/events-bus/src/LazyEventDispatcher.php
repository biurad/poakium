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

use DivineNii\Invoker\Interfaces\InvokerInterface;
use DivineNii\Invoker\Invoker;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * {@inheritdoc}
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LazyEventDispatcher extends EventDispatcher
{
    /** @var InvokerInterface */
    private $resolver;

    /**
     * @param null|InvokerInterface $invoker
     */
    public function __construct(?InvokerInterface $invoker = null)
    {
        parent::__construct();
        $this->resolver = $invoker ?? new Invoker();
    }

    /**
     * @return InvokerInterface
     */
    public function getResolver(): InvokerInterface
    {
        return $this->resolver;
    }

    /**
     * {@inheritdoc}
     */
    protected function callListeners(iterable $listeners, string $eventName, object $event): void
    {
        foreach ($listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            try {
                $listener($event, $eventName, $this);
            } catch (\TypeError $error) {
                $this->resolver->call($listener, [
                    \get_class($event)  => $event,
                    'eventName'         => $eventName,
                    __CLASS__           => $this,
                ]);
            }
        }
    }
}
