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

namespace BiuradPHP\Security\EventListeners;

use BiuradPHP\Security\Event\LoginEvent;
use BiuradPHP\Security\SecurityEvents;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;

class UserLoginListener implements EventSubscriberInterface
{
    private $listeners = [];

    public function __construct(iterable $listeners)
    {
        $this->listeners = iterator_to_array($listeners);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::LOGIN_RESPONSE => ['onLoginResponse', -128]
        ];
    }

    public function onLoginResponse(LoginEvent $event)
    {
        foreach ($this->listeners as $listener) {
            $listener($event);

            if ($event->hasResponse()) {
                break;
            }
        }
    }
}
