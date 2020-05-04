<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\Concerns;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use BiuradPHP\Events\Interfaces\EventDispatcherInterface as BiuradEventDispatcher;

class FakeEventDispatcher implements EventDispatcherInterface
{
    private $dispatcher;

    public function __construct(BiuradEventDispatcher $events)
    {
        $this->dispatcher = $events;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        $object = $this->dispatcher->dispatch($event);

        return is_object($object) ? $object : $event;
    }
}
