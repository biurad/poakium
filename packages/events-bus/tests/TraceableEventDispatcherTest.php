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

namespace Biurad\Events\Tests;

use Biurad\Events\LazyEventDispatcher;
use Biurad\Events\TraceableEventDispatcher;
use DivineNii\Invoker\Invoker;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TraceableEventDispatcherTest extends TestCase
{
    public function testAddRemoveListener(): void
    {
        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());

        $tdispatcher->addListener(
            'foo',
            $listener = function (): void {
            }
        );
        $listeners = $dispatcher->getListeners('foo');

        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]);

        $tdispatcher->removeListener('foo', $listener);

        $this->assertCount(0, $dispatcher->getListeners('foo'));
    }

    public function testGetListeners(): void
    {
        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());

        $tdispatcher->addListener(
            'foo',
            $listener = function (): void {
            }
        );

        $this->assertSame($dispatcher->getListeners('foo'), $tdispatcher->getListeners('foo'));
    }

    public function testHasListeners(): void
    {
        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());

        $this->assertFalse($dispatcher->hasListeners('foo'));
        $this->assertFalse($tdispatcher->hasListeners('foo'));

        $tdispatcher->addListener(
            'foo',
            $listener = function (): void {
            }
        );

        $this->assertTrue($dispatcher->hasListeners('foo'));
        $this->assertTrue($tdispatcher->hasListeners('foo'));
    }

    public function testGetListenerPriority(): void
    {
        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());

        $tdispatcher->addListener(
            'foo',
            function (): void {
            },
            123
        );

        $listeners = $dispatcher->getListeners('foo');

        $this->assertSame(123, $tdispatcher->getListenerPriority('foo', $listeners[0]));

        // Verify that priority is preserved when listener is removed and re-added
        // in preProcess() and postProcess().
        $tdispatcher->dispatch(new Event(), 'foo');
        $listeners = $dispatcher->getListeners('foo');

        $this->assertSame(123, $tdispatcher->getListenerPriority('foo', $listeners[0]));
    }

    public function testGetListenerPriorityWhileDispatching(): void
    {
        $tdispatcher              = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $priorityWhileDispatching = null;

        $listener = function () use ($tdispatcher, &$priorityWhileDispatching, &$listener): void {
            $priorityWhileDispatching = $tdispatcher->getListenerPriority('bar', $listener);
        };

        $tdispatcher->addListener('bar', $listener, 5);
        $tdispatcher->dispatch(new Event(), 'bar');
        $this->assertSame(5, $priorityWhileDispatching);
    }

    public function testAddRemoveSubscriber(): void
    {
        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());

        $subscriber = new Fixtures\EventSubscriber();

        $tdispatcher->addSubscriber($subscriber);
        $listeners = $dispatcher->getListeners('foo');
        $this->assertCount(1, $listeners);
        $this->assertSame([$subscriber, 'call'], $listeners[0]);

        $tdispatcher->removeSubscriber($subscriber);
        $this->assertCount(0, $dispatcher->getListeners('foo'));
    }

    public function testGetCalledListeners(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->addListener(
            'foo',
            function (): void {
            },
            5
        );

        $expected = ['event' => 'foo', 'pretty' => 'closure', 'priority' => 5, 'duration' => null];

        $listeners = $tdispatcher->getNotCalledListeners();
        $this->assertEquals([], $tdispatcher->getCalledListeners());
        $this->assertEquals([$expected], $listeners);

        $tdispatcher->dispatch(new Event(), 'foo');

        $listeners = $tdispatcher->getCalledListeners();
        $this->assertNotEquals([$expected], $listeners);
        $this->assertNotNull(\current($listeners)['duration']);
        $this->assertEquals([], $tdispatcher->getNotCalledListeners());
    }

    public function testClearCalledListeners(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->addListener(
            'foo',
            function (): void {
            },
            5
        );

        $tdispatcher->dispatch(new Event(), 'foo');
        $tdispatcher->reset();

        $listeners = $tdispatcher->getNotCalledListeners();
        $expected  = ['event' => 'foo', 'pretty' => 'closure', 'priority' => 5, 'duration' => null];
        $this->assertEquals([], $tdispatcher->getCalledListeners());
        $this->assertEquals([$expected], $listeners);
    }

    public function testNotCalledListenersWithException(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->method('getListeners')->willThrowException($e = new \RuntimeException());

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $tdispatcher = new TraceableEventDispatcher($dispatcher, $logger);
        $tdispatcher->addListener(
            'foo',
            function (): void {
            }
        );

        $this->assertEquals([], $tdispatcher->getNotCalledListeners());
        $logger->expects($this->exactly(0))->method('info')->with('An exception was thrown while getting the uncalled listeners.', ['exception' => $e]);
    }

    public function testDispatchAfterReset(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->addListener('foo', function (): void {
        }, 5);

        $tdispatcher->reset();
        $tdispatcher->dispatch(new Event(), 'foo');

        $listeners = $tdispatcher->getCalledListeners();
        $this->assertArrayHasKey('event', $listeners[0]);
    }

    public function testGetCalledListenersNested(): void
    {
        $tdispatcher = null;
        $dispatcher  = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());

        $dispatcher->addListener(
            'foo',
            function (Event $event, $eventName, $dispatcher) use (&$tdispatcher): void {
                $tdispatcher = $dispatcher;
                $dispatcher->dispatch(new Event(), 'bar');
            }
        );
        $dispatcher->addListener(
            'bar',
            function (Event $event): void {
            }
        );
        $dispatcher->dispatch(new Event(), 'foo');

        $this->assertSame($dispatcher, $tdispatcher);
        $this->assertCount(2, $dispatcher->getCalledListeners());
    }

    public function testItReturnsNoOrphanedEventsWhenCreated(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $events      = $tdispatcher->getOrphanedEvents();
        $this->assertEmpty($events);
    }

    public function testItReturnsOrphanedEventsAfterDispatch(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->dispatch(new Event(), 'foo');
        $events = $tdispatcher->getOrphanedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals(['foo'], $events);
    }

    public function testItDoesNotReturnHandledEvents(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->addListener('foo', function (): void {
        });
        $tdispatcher->dispatch(new Event(), 'foo');
        $events = $tdispatcher->getOrphanedEvents();
        $this->assertEmpty($events);
    }

    public function testLogger(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, $logger);

        $tdispatcher->addListener(
            'foo',
            $listener1 = function (): void {
            }
        );
        $tdispatcher->addListener(
            'foo',
            $listener2 = function (): void {
            }
        );

        $tdispatcher->dispatch(new Event(), 'foo');

        $logger->expects($this->exactly(0))->method('debug')->with('Notified event "{event}" to listener "{listener}".', ['event' => 'foo', 'listener' => 'closure']);
    }

    public function testLoggerWithStoppedEvent(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, $logger);
        $tdispatcher->addListener(
            'foo',
            $listener1 = function (Event $event): void {
                $event->stopPropagation();
            }
        );
        $tdispatcher->addListener(
            'foo',
            $listener2 = function (): void {
            }
        );

        $tdispatcher->dispatch(new Event(), 'foo');

        $logger->expects($this->exactly(0))->method('debug')->with('Notified event "{event}" to listener "{listener}".', ['event' => 'foo', 'listener' => 'closure']);
        $logger->expects($this->exactly(0))->method('debug')->with('Listener "{listener}" stopped propagation of the event "{event}".', ['event' => 'foo', 'listener' => 'closure']);
        $logger->expects($this->exactly(0))->method('debug')->with('Listener "{listener}" was not called for event "{event}".', ['event' => 'foo', 'listener' => 'closure']);
    }

    public function testDispatchCallListeners(): void
    {
        $called = [];

        $dispatcher  = new EventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());
        $tdispatcher->addListener(
            'foo',
            function () use (&$called): void {
                $called[] = 'foo1';
            },
            10
        );
        $tdispatcher->addListener(
            'foo',
            function () use (&$called): void {
                $called[] = 'foo2';
            },
            20
        );

        $tdispatcher->dispatch(new Event(), 'foo');

        $this->assertSame(['foo2', 'foo1'], $called);
    }

    public function testLazyDispatchCallListeners(): void
    {
        $called = [];

        $dispatcher  = new LazyEventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());
        $tdispatcher->addListener(
            'foo',
            function (Event $event, $eventName, EventDispatcherInterface $dispatcher, $hello = 'Divine') use (&$called): void {
                $called[] = $hello;
            },
            10
        );
        $tdispatcher->addListener(
            'foo',
            function (Event $event, NullLogger $logger, EventDispatcherInterface $dispatcher) use (&$called): void {
                $called[] = $logger;
            },
            20
        );

        $tdispatcher->dispatch(new Event(), 'foo');

        $this->assertNotEmpty($called);
        $this->assertEquals('Divine', $called[1]);
        $this->assertInstanceOf(LoggerInterface::class, $called[0]);
    }

    public function testLazyDispatchWithContainerAndCallListeners(): void
    {
        $called               = [];
        $container            = new class () implements ContainerInterface {
            private $services = [
                'logger' => NullLogger::class,
            ];

            public function has($id)
            {
                return isset($this->services[$id]);
            }

            public function get($id)
            {
                if ($this->has($id)) {
                    return new $this->services[$id]();
                }

                throw new class () extends \Exception implements NotFoundExceptionInterface {
                };
            }
        };

        $dispatcher  = new LazyEventDispatcher(new Invoker([], $container));
        $tdispatcher = new TraceableEventDispatcher($dispatcher);
        $tdispatcher->addListener(
            'foo',
            function (Event $event, EventDispatcherInterface $dispatcher, $logger) use (&$called): void {
                $called[] = $logger;
            }
        );

        $tdispatcher->dispatch(new Event(), 'foo');

        $this->assertNotEmpty($called);
        $this->assertInstanceOf(LoggerInterface::class, $called[0]);
    }

    public function testLazyDispatchWithStoppedPropagationAndLogger(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $dispatcher  = new LazyEventDispatcher();
        $tdispatcher = new TraceableEventDispatcher($dispatcher, $logger);
        $tdispatcher->addListener(
            'foo',
            function (Event $event, $eventName, $dispatcher): void {
            }
        );

        $event = new Event();
        $event->stopPropagation();
        $tdispatcher->dispatch($event, 'foo');

        $logger->expects($this->exactly(0))->method('debug')->with('The "foo" event is already stopped. No listeners have been called.');

        $events = $tdispatcher->getOrphanedEvents();
        $this->assertCount(0, $events);
        $this->assertEquals([], $events);
        $this->assertCount(1, $tdispatcher->getEventsLogs());
    }

    public function testDispatchNested(): void
    {
        $dispatcher       = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $loop             = 1;
        $dispatchedEvents = 0;
        $dispatcher->addListener(
            'foo',
            $listener1 = function () use ($dispatcher, &$loop): void {
                ++$loop;

                if (2 == $loop) {
                    $dispatcher->dispatch(new Event(), 'foo');
                }
            }
        );
        $dispatcher->addListener(
            'foo',
            function () use (&$dispatchedEvents): void {
                ++$dispatchedEvents;
            }
        );

        $dispatcher->dispatch(new Event(), 'foo');

        $this->assertSame(2, $dispatchedEvents);
    }

    public function testDispatchReusedEventNested(): void
    {
        $nestedCall = false;
        $dispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $dispatcher->addListener(
            'foo',
            function (Event $e) use ($dispatcher): void {
                $dispatcher->dispatch(new Event(), 'bar', $e);
            }
        );
        $dispatcher->addListener(
            'bar',
            function (Event $e) use (&$nestedCall): void {
                $nestedCall = true;
            }
        );

        $this->assertFalse($nestedCall);
        $dispatcher->dispatch(new Event(), 'foo');
        $this->assertTrue($nestedCall);
    }

    public function testListenerCanRemoveItselfWhenExecuted(): void
    {
        $eventDispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $listener1       = function ($event, $eventName, EventDispatcherInterface $dispatcher) use (&$listener1): void {
            $dispatcher->removeListener('foo', $listener1);
        };
        $eventDispatcher->addListener('foo', $listener1);
        $eventDispatcher->addListener(
            'foo',
            function (): void {
            }
        );
        $eventDispatcher->dispatch(new Event(), 'foo');

        $this->assertCount(1, $eventDispatcher->getListeners('foo'), 'expected listener1 to be removed');
    }

    public function testClearOrphanedEvents(): void
    {
        $tdispatcher = new TraceableEventDispatcher(new EventDispatcher(), new NullLogger());
        $tdispatcher->dispatch(new Event(), 'foo');
        $events = $tdispatcher->getOrphanedEvents();
        $this->assertCount(1, $events);
        $tdispatcher->reset();
        $events = $tdispatcher->getOrphanedEvents();
        $this->assertCount(0, $events);
    }
}
