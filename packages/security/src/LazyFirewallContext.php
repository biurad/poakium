<?php /** @noinspection PhpUndefinedMethodInspection */

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

namespace BiuradPHP\Security;

use BiuradPHP\Security\Config\FirewallConfig;
use BiuradPHP\Security\Event\LazyResponseEvent;
use BiuradPHP\Security\Firewalls\AbstractListener;
use BiuradPHP\Security\Firewalls\ExceptionListener;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Firewalls\LogoutListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Lazily calls authentication listeners when actually required by the access listener.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class LazyFirewallContext extends FirewallContext
{
    private $tokenStorage;

    public function __construct(iterable $listeners, ?ExceptionListener $exceptionListener, ?LogoutListener $logoutListener, ?FirewallConfig $config, TokenStorage $tokenStorage)
    {
        parent::__construct($listeners, $exceptionListener, $logoutListener, $config);

        $this->tokenStorage = $tokenStorage;
    }

    public function getListeners(): iterable
    {
        return [$this];
    }

    public function __invoke(RequestEvent $event)
    {
        $listeners = [];
        $request = $event->getRequest();
        $lazy = in_array($request->getMethod(), ['GET', 'HEAD'], true);

        foreach (parent::getListeners() as $listener) {
            if (!$lazy || !$listener instanceof AbstractListener) {
                $listeners[] = $listener;
                $lazy = $lazy && $listener instanceof AbstractListener;
            } elseif (false !== $supports = $listener->supports($event)) {
                $listeners[] = [$listener, 'authenticate'];
                $lazy = null === $supports;
            }
        }

        if (!$lazy) {
            foreach ($listeners as $listener) {
                $listener($event);

                if ($event->hasResponse()) {
                    return;
                }
            }

            return;
        }

        $this->tokenStorage->setInitializer(function () use ($event, $listeners) {
            $event = new LazyResponseEvent($event);
            foreach ($listeners as $listener) {
                $listener($event);
            }

            if ($event->hasAuthenticationToken()) {
                $this->tokenStorage->setToken($event->getAuthenticationToken());
            }
        });
    }
}
