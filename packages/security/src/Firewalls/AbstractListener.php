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

namespace BiuradPHP\Security\Firewalls;

use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use Flight\Routing\Interfaces\RouteCollectorInterface;
use Flight\Routing\Interfaces\RouteInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

/**
 * A base class for listeners that can tell whether they should authenticate incoming requests.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractListener
{
    final public function __invoke(RequestEvent $event)
    {
        if (false !== $this->supports($event)) {
            $this->authenticate($event);
        }
    }

    /**
     * Tells whether the authenticate() method should be called or not depending on the incoming request.
     *
     * Returning null means authenticate() can be called lazily when accessing the token storage.
     * @param RequestEvent $event
     *
     * @return bool|null
     */
    abstract public function supports(RequestEvent $event): ?bool;

    /**
     * Does whatever is required to authenticate the request, typically calling $event->setResponse() internally.
     *
     * @param RequestEvent $event
     */
    abstract public function authenticate(RequestEvent $event);

    /**
     * Checks that a given path matches the Request.
     *
     * @param Request $request
     * @param string $path A path (an absolute path (/foo))
     * @param RouteCollectorInterface|null $router
     *
     * @return bool true if the path is the same as the one from the Request, false otherwise
     */
    protected function checkRequestPath(Request $request, string $path, ?RouteCollectorInterface $router = null)
    {
        $router     = $request->getAttribute('router', $router);
        $baseUri    = $router->currentRoute()->getPath();

        try {
            $namedUri = $router->getNamedRoute($path);
        } catch (RuntimeException $e) {
            $namedUri = null;
        }

        if (
            $namedUri instanceof RouteInterface &&
            rtrim($baseUri, '/') === rtrim($namedUri->getPath(), '/')
        ) {
            return true;
        }

        // matching a request is more powerful than matching a URL path + context, so try that first
        if (rtrim($path, '/') !== rtrim($baseUri, '/')) {
            return false;
        }

        return $path === rawurldecode($request->getUri()->getPath());
    }
}
