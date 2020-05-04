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

namespace BiuradPHP\Security\Middlewares;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Security\Event\LoginEvent;
use BiuradPHP\Security\EventListeners\UserLoginListener;
use BiuradPHP\Security\EventListeners\VoteListener;
use BiuradPHP\Security\Firewalls\AccessListener;
use BiuradPHP\Security\Firewalls\ExceptionListener;
use BiuradPHP\Security\Firewalls\LogoutListener;
use BiuradPHP\Security\Interfaces\FirewallMapInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SplObjectStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;

/**
 * Firewall uses a FirewallMap to register security listeners for the given
 * request.
 *
 * It allows for different security strategies within the same application
 * (a Basic authentication for the /api, and a web based authentication for
 * everything else for instance).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FirewallMiddleware implements MiddlewareInterface
{
    private $map;
    private $dispatcher;
    private $tokenStorage;
    private $exceptionListeners;

    public const REDIRECT_STATUS = 302;

    public function __construct(FirewallMapInterface $map, TokenStorageInterface $tokenStorage, EventDispatcherInterface $dispatcher)
    {
        $this->map = $map;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
        $this->exceptionListeners = new SplObjectStorage();
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return ResponseInterface
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // register listeners for this firewall
        $listeners = $this->map->getListeners($request);
        $authenticationListeners = $listeners[0];

        /** @var ExceptionListener $exceptionListener */
        $exceptionListener = $listeners[1];

        /** @var LogoutListener $logoutListener */
        $logoutListener = $listeners[2];

        if (null !== $exceptionListener) {
            $this->exceptionListeners[$request] = $exceptionListener;
            $exceptionListener->register($this->dispatcher);
        }

        $authenticationListeners = function () use ($authenticationListeners, $logoutListener) {
            $accessListener = null;

            foreach ($authenticationListeners as $listener) {
                if ($listener instanceof AccessListener) {
                    $accessListener = $listener;

                    continue;
                }

                yield $listener;
            }

            if (null !== $logoutListener) {
                yield $logoutListener;
            }

            if (null !== $accessListener) {
                yield $accessListener;
            }
        };

        $this->addEventsListeners($this->dispatcher, $authenticationListeners()); // Add Events Listeners before calling Listeners.

        return $this->callListeners($request, $handler);
    }

    protected function callListeners(Request $request, RequestHandler $handler)
    {
        $event = new LoginEvent($request);
        $this->dispatcher->dispatch($event);

        if ($event->hasAuthenticationToken()) {
            $this->tokenStorage->setToken($event->getAuthenticationToken());
        }

        // The original response.
        $response = clone $handler->handle($event->getRequest());

        // Return new response or original response.
        return $event->hasResponse() ? $response = $event->getResponse() : $response;
    }

    protected function addEventsListeners(EventDispatcherInterface $dispatcher, iterable $listeners)
    {
        $dispatcher->addSubscriber(new UserLoginListener($listeners));
        $dispatcher->addSubscriber(VoteListener::class . '@' . TraceableAccessDecisionManager::class);
    }
}
