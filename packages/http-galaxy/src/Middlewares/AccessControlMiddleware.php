<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Middlewares;

use BiuradPHP\Http\Strategies\AccessControlPolicy;
use BiuradPHP\Http\Strategies\RequestMatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Handles Cors HTTP Middleware.
 *
 * This determines what cross-origin operations may execute
 * in web browsers.
 *
 * @see     https://www.w3.org/TR/cors/
 * @see     https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 *
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
class AccessControlMiddleware implements MiddlewareInterface
{
    /** @var AccessControlPolicy */
    private $cors;

    public function __construct(?AccessControlPolicy $accessControl, array $options = [])
    {
        $this->cors = $accessControl ?? new AccessControlPolicy($options);
    }

    /**
     * {@inheritDoc}
     *
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @return ResponseInterface
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Check if we're dealing with CORS and if we should handle it
        if (!$this->shouldRun($request)) {
            return $response;
        }

        // For Preflight, return the Preflight response
        if ($this->cors->isPreflightRequest($request)) {
            return $this->cors->handlePreflightRequest($request, $response);
        }

        // If the request is not allowed, return 403
        if (!$this->cors->isActualRequestAllowed($request)) {
            return $response->withStatus(403, 'Not allowed in CORS policy.');
        }

        // Add the CORS headers to the Response
        return $this->addHeaders($request, $response);
    }

    /**
     * Determine if the request has a URI that should pass through the CORS flow.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function shouldRun(Request $request): bool
    {
        // Check if this is an actual CORS request
        if (!$this->cors->isCorsRequest($request)) {
            return false;
        }

        return $this->isMatchingPath($request);
    }

    /**
     * The the path from the config, to see if the CORS Service should run
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isMatchingPath(Request $request): bool
    {
        // Get the paths from the config or the middleware
        $paths   = $this->cors->getPaths();
        $matcher =  new RequestMatcher();

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = \trim($path, '/');
            }

            $matcher->matchPath($path);

            if ($matcher->matches($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add the headers to the Response, if they don't exist yet.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    private function addHeaders(Request $request, ResponseInterface $response): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->cors->addActualRequestHeaders($response, $request);
        }

        return $response;
    }
}
