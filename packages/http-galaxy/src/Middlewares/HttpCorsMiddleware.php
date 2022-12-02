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

namespace Biurad\Http\Middlewares;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Handles Cors HTTP Middleware.
 *
 * This determines what cross-origin operations may execute
 * in web browsers, adds CORS headers and handles pre-flight requests.
 *
 * @see     https://www.w3.org/TR/cors/
 * @see     https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 *
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class HttpCorsMiddleware implements MiddlewareInterface
{
    /** @var array<string,mixed> */
    protected $options;

    /**
     * Simple headers as defined in the spec should always be accepted.
     */
    protected static $simpleHeaders = [
        'accept',
        'accept-language',
        'content-language',
        'origin',
        'expires',
    ];

    public function __construct(array $options = [])
    {
        $this->options = $options += [
            'allow_origin' => [],
            'allow_paths' => [],
            'origin_regex' => false,
            'allow_credentials' => false,
            'allow_headers' => null,
            'expose_headers' => null,
            'allow_methods' => [],
            'max_age' => 0,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Check if we're dealing with CORS and if we should handle it
        if (!$this->isMatchingPath($request->getUri(), $request->getServerParams()['PATH_INFO'] ?? '')) {
            return $response;
        }

        // skip if not a CORS request
        if (!$this->hasOrigin($request)) {
            return $response->withoutHeader('Access-Control-Allow-Origin');
        }

        // perform preflight checks
        if ('OPTIONS' === $request->getMethod() && $request->hasHeader('Access-Control-Request-Method')) {
            return $this->getPreflightResponse($request, $response);
        }

        return $response;
    }

    /**
     * For Preflight, return the Preflight response.
     */
    protected function getPreflightResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', true === $this->options['allow_origin'] ? '*' : $request->getHeaderLine('Origin'))
            ->withHeader('Vary', $response->hasHeader('Vary') ? $response->getHeaderLine('Vary') . ', Origin' : 'Origin');

        if ($this->options['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // check request headers
        if (isset($this->options['allow_headers'])) {
            $allowedHeaders = $this->options['allow_headers'];

            if ($request->hasHeader('Access-Control-Request-Headers')) {
                $headers = \trim($request->getHeaderLine('Access-Control-Request-Headers'));

                foreach (\preg_split('{, *}', $headers) as $header) {
                    if (\in_array(\strtolower($header), self::$simpleHeaders, true)) {
                        continue;
                    }

                    if (\is_array($allowedHeaders) && !\in_array($header, $allowedHeaders, true)) {
                        return $response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
                    }
                }
            }

            if (isset($headers) || (\is_array($allowedHeaders) && !empty($allowedHeaders))) {
                $response = $response->withHeader('Access-Control-Allow-Headers', $headers ?? \implode(', ', $allowedHeaders));
            }
        }

        if (isset($this->options['expose_headers'])) {
            $exposedHeaders = [];

            foreach ((array) $this->options['expose_headers'] as $header) {
                if (\in_array(\strtolower($header), self::$simpleHeaders, true)) {
                    continue;
                }

                $exposedHeaders[] = $header;
            }

            if (!empty($exposedHeaders)) {
                $response = $response->withHeader('Access-Control-Expose-Headers', \implode(', ', $exposedHeaders));
            }
        }

        if ($this->options['max_age'] > 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $this->options['max_age']);
        }

        if (!empty($allowedMethods = $this->options['allow_methods'])) {
            $requestMethod = \strtoupper($request->getHeaderLine('Access-Control-Request-Method'));

            /*
             * We have to allow the header in the case-set as we received it by the client.
             * Firefox f.e. sends the LINK method as "Link", and we have to allow it like this or the browser will deny the request.
             */
            if (isset($allowedMethods['Link'])) {
                $allowedMethods[] = 'link';
            }

            // check request method
            if (!\in_array($requestMethod, $allowedMethods, true)) {
                return $response->withStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
            }

            $response = $response->withHeader('Access-Control-Allow-Methods', \implode(', ', $allowedMethods));
        }

        return $response;
    }

    /**
     * Checks the request Origin and whether or not has same host.
     */
    protected function hasOrigin(RequestInterface $request): bool
    {
        if (true === $allowedOrigin = $this->options['allow_origin']) {
            return true;
        }

        if (empty($requestOrigin = $request->getHeaderLine('Origin'))) {
            goto cors_same_host;
        }

        if (\is_array($allowedOrigin) && !empty($allowedOrigin)) {
            $allowedOrigin = (array) $allowedOrigin;

            // origin regex matching
            if (true === $this->options['origin_regex']) {
                foreach ($allowedOrigin as $originRegexp) {
                    if (!\is_string($originRegexp)) {
                        continue;
                    }

                    if (\preg_match('{' . $originRegexp . '}i', $requestOrigin)) {
                        return true;
                    }
                }
            } elseif (\in_array($requestOrigin, $allowedOrigin, true)) {
                return true; // old origin matching
            }
        }

        cors_same_host: // Checks if Origin header value is same as domain.
        $requestHost = ($requestUri = $request->getUri())->getScheme() . '://' . $requestUri->getHost();

        return !$requestOrigin === $requestHost || !$requestOrigin === (string) $requestUri;
    }

    /**
     * The the path from the config, to see if the CORS Service should run.
     */
    private function isMatchingPath(UriInterface $requestUri, string $pathInfo): bool
    {
        if (true === $paths = $this->options['allow_paths']) {
            return true;
        }

        // Support matching sub-directory sites ...
        $requestPath = !empty($pathInfo) ? $pathInfo : $requestUri->getPath();

        if (isset($paths[$requestPath])) {
            $this->options = \array_merge($this->options, $paths[$requestPath]);

            return true;
        }

        foreach ($paths as $pathRegexp => $options) {
            if (1 === \preg_match('{' . \preg_quote($pathRegexp, '/') . '}', $requestPath)) {
                $this->options = \array_merge($this->options, $options);

                return true;
            }
        }

        return false;
    }
}
