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

use ArrayObject;
use Biurad\Http\Strategies\RequestMatcher;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
class AccessControlMiddleware implements MiddlewareInterface
{
    /** @var ArrayObject */
    protected $options;

    /**
     * Simple headers as defined in the spec should always be accepted
     */
    protected static $simpleHeaders = [
        'accept',
        'accept-language',
        'content-language',
        'origin',
    ];

    public function __construct(array $options = [])
    {
        $this->options = new ArrayObject($this->normalizeOptions($options));
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Check if we're dealing with CORS and if we should handle it
        if (!$this->isMatchingPath($request)) {
            return $response;
        }

        // skip if not a CORS request
        if (!$request->hasHeader('Origin') || $this->isSameHost($request)) {
            return $response;
        }

        // perform preflight checks
        if ('OPTIONS' === $request->getMethod() && $request->hasHeader('Access-Control-Request-Method')) {
            return $this->getPreflightResponse($request, $response);
        }

        $response;
    }

    /**
     * For Preflight, return the Preflight response
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function getPreflightResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $options = $this->options;

        if (!$response->hasHeader('Vary')) {
            $response = $response->withHeader('Vary', 'Origin');
        } else {
            $response = $response->withHeader('Vary', $response->getHeaderLine('Vary') . ', Origin');
        }

        if ($options['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($options['allow_methods'])) {
            $response = $response->withHeader('Access-Control-Allow-Methods', \implode(', ', $options['allow_methods']));
        }

        if ($options['allow_headers']) {
            $headers = $this->isWildcard('allow_headers')
                ? $request->getHeaderLine('Access-Control-Request-Headers')
                : \implode(', ', $options['allow_headers']);

            if ($headers) {
                $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
            }
        }

        if ($options['max_age']) {
            $response = $response->withHeader('Access-Control-Max-Age', $options['max_age']);
        }

        if (!$this->checkOrigin($request)) {
            return $response->withoutHeader('Access-Control-Allow-Origin');
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

        // check request method
        if (!\in_array(\strtoupper($request->getHeaderLine('Access-Control-Request-Method')), $options['allow_methods'], true)) {
            return $response->withStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
        }

        /*
         * We have to allow the header in the case-set as we received it by the client.
         * Firefox f.e. sends the LINK method as "Link", and we have to allow it like this or the browser will deny the
         * request.
         */
        if (!\in_array($request->getHeaderLine('Access-Control-Request-Method'), $options['allow_methods'], true)) {
            $options['allow_methods'][] = $request->getHeaderLine('Access-Control-Request-Method');

            $response = $response->withHeader('Access-Control-Allow-Methods', \implode(', ', $options['allow_methods']));
        }

        // check request headers
        $headers = $request->getHeaderLine('Access-Control-Request-Headers');

        if ($headers && !$this->isWildcard('allow_headers')) {
            $headers = \strtolower(\trim($headers));

            foreach (\preg_split('{, *}', $headers) as $header) {
                if (\in_array(\strtolower($header), self::$simpleHeaders, true)) {
                    continue;
                }

                if (!\in_array($header, $options['allow_headers'], true)) {
                    $response = $response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);

                    break;
                }
            }
        }

        return $response;
    }

    /**
     * Checks the Origin.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    protected function checkOrigin(RequestInterface $request): bool
    {
        // check origin
        $origin = $request->getHeaderLine('Origin');

        if ($this->isWildcard('allow_origin')) {
            return true;
        }

        if ($this->options['origin_regex'] === true) {
            // origin regex matching
            foreach ($this->options['allow_origin'] as $originRegexp) {
                if (\preg_match('{' . $originRegexp . '}i', $origin)) {
                    return true;
                }
            }
        } else {
            // old origin matching
            if (\in_array($origin, $this->options['allow_origin'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The the path from the config, to see if the CORS Service should run
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function isMatchingPath(RequestInterface $request): bool
    {
        $paths   = $this->options['allow_paths'];
        $matcher = new RequestMatcher();

        if (is_bool($paths) && $this->isWildcard('allow_paths')) {
            return true;
        }

        foreach ($paths as $pathRegexp => $options) {
            $matcher->matchPath($pathRegexp);

            // skip if the host is not matching
            $matcher->matchHost($options['hosts'] ?? []);

            if ($matcher->matches($request)) {
                $this->options = \array_merge($this->options, $options);

                return true;
            }
        }

        return false;
    }

    /**
     * Is the option a wildcard type
     *
     * @param string $option
     *
     * @return bool
     */
    private function isWildcard(string $option): bool
    {
        return $this->options[$option] === true || (\is_array($this->options[$option]) && \in_array('*', $this->options[$option]));
    }

    /**
     * Checks if Origin header value is same as domain.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isSameHost(ServerRequestInterface $request): bool
    {
        $uri = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();

        return $request->getHeaderLine('Origin') === $uri;
    }

    /**
     * Normalize the options for usage
     *
     * @param array $options
     *
     * @return array
     */
    private function normalizeOptions(array $options = []): array
    {
        $options = \array_merge([
            'allow_origin'      => [],
            'allow_paths'       => [],
            'origin_regex'      => false,
            'allow_credentials' => false,
            'allow_headers'     => [],
            'expose_headers'    => [],
            'allow_methods'     => [],
            'max_age'           => 0,
        ], $options);

        // normalize array('*') to true
        foreach (['allow_origin', 'allow_headers', 'allow_paths'] as $wildcard) {
            if ($options[$wildcard] === '*') {
                $options[$wildcard] = true;
            }
        }

        return $options;
    }
}
