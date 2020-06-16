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

namespace BiuradPHP\Http\Cors;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles Cors HTTP header.
 *
 * Provides tools for cross-origin resource sharing or "CORS".
 * This determines what cross-origin operations may execute in web browsers.
 *
 * @see     https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 * @see     https://www.w3.org/TR/cors/
 *
 * @author  Alexander <iam.asm89@gmail.com>
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 *
 * @internal
 */
class AccessControl
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isActualRequestAllowed(ServerRequestInterface $request): bool
    {
        return $this->checkOrigin($request);
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->options['allowedPaths'];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isCorsRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Origin') && !$this->isSameHost($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isPreflightRequest(ServerRequestInterface $request): bool
    {
        return $this->isCorsRequest($request)
            && $request->getMethod() === 'OPTIONS'
            && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * @param ResponseInterface      $response
     * @param ServerRequestInterface $request
     *
     * @return Response
     */
    public function addActualRequestHeaders(Response $response, ServerRequestInterface $request): Response
    {
        if (!$this->checkOrigin($request)) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

        if (!$response->hasHeader('Vary')) {
            $response = $response->withHeader('Vary', 'Origin');
        } else {
            $response = $response->withHeader('Vary', $response->getHeaderLine('Vary') . ', Origin');
        }

        if ($this->options['allowCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->options['exposedHeaders']) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                \implode(', ', $this->options['exposedHeaders'])
            );
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Response               $response
     *
     * @return Response
     */
    public function handlePreflightRequest(ServerRequestInterface $request, Response $response): Response
    {
        if (true !== $check = $this->checkPreflightRequestConditions($response, $request)) {
            return $check;
        }

        return $this->buildPreflightCheckResponse($request, $response);
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
            'allowedOrigins'         => [],
            'allowedPaths'           => [],
            'allowedOriginsPatterns' => [],
            'allowCredentials'       => false,
            'allowedHeaders'         => [],
            'exposedHeaders'         => [],
            'allowedMethods'         => [],
            'maxAge'                 => 0,
        ], $options);

        // Transform wildcard pattern
        foreach ($options['allowedOrigins'] as $origin) {
            if (\strpos($origin, '*') !== false) {
                $options['allowedOriginsPatterns'][] = $this->convertWildcardToPattern($origin);
            }
        }

        // normalize array('*') to true
        if (\in_array('*', $options['allowedOrigins'], true)) {
            $options['allowedOrigins'] = true;
        }

        if (\in_array('*', $options['allowedHeaders'], true)) {
            $options['allowedHeaders'] = true;
        } else {
            $options['allowedHeaders'] = \array_map('strtolower', $options['allowedHeaders']);
        }

        if (\in_array('*', $options['allowedMethods'], true)) {
            $options['allowedMethods'] = true;
        } else {
            $options['allowedMethods'] = \array_map('strtoupper', $options['allowedMethods']);
        }

        return $options;
    }

    /**
     * Create a pattern for a wildcard, based on Str::is() from Laravel
     *
     * @param string $pattern
     *
     * @return string
     *
     * @see https://github.com/laravel/framework/blob/5.5/src/Illuminate/Support/Str.php
     */
    private function convertWildcardToPattern($pattern): string
    {
        $pattern = \preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = \str_replace('\*', '.*', $pattern);

        return '#^' . $pattern . '\z#u';
    }

    /**
     * Build the Cors Headers
     *
     * @param ServerRequestInterface $request
     * @param Response               $response
     *
     * @return Response
     */
    private function buildPreflightCheckResponse(ServerRequestInterface $request, Response $response): Response
    {
        if ($this->options['allowCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));

        if ($this->options['maxAge']) {
            $response = $response->withHeader('Access-Control-Max-Age', $this->options['maxAge']);
        }

        $allowMethods = $this->options['allowedMethods'] === true
            ? \strtoupper($request->getHeaderLine('Access-Control-Request-Method'))
            : \implode(', ', $this->options['allowedMethods']);
        $response = $response->withHeader('Access-Control-Allow-Methods', $allowMethods);

        $allowHeaders = $this->options['allowedHeaders'] === true
            ? \strtoupper($request->getHeaderLine('Access-Control-Request-Headers'))
            : \implode(', ', $this->options['allowedHeaders']);
        $response = $response->withHeader('Access-Control-Allow-Headers', $allowHeaders);

        return $response->withStatus(204);
    }

    /**
     * @param Response               $response
     * @param ServerRequestInterface $request
     *
     * @return bool|Response
     */
    private function checkPreflightRequestConditions(Response $response, ServerRequestInterface $request)
    {
        if (!$this->checkOrigin($request)) {
            return $response->withStatus(403, 'Origin not allowed');
        }

        if (!$this->checkMethod($request)) {
            return $response->withStatus(405, 'Method not allowed');
        }

        $requestHeaders = [];
        // if allowedHeaders has been set to true ('*' allow all flag) just skip this check
        if ($this->options['allowedHeaders'] !== true && $request->hasHeader('Access-Control-Request-Headers')) {
            $headers        = \strtolower($request->getHeaderLine('Access-Control-Request-Headers'));
            $requestHeaders = \array_filter(\explode(',', $headers));

            foreach ($requestHeaders as $header) {
                if (!\in_array(\trim($header), $this->options['allowedHeaders'])) {
                    return $response->withStatus(403, 'Header not allowed');
                }
            }
        }

        return true;
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
        return $request->getHeaderLine('Origin') === $request->getUri()->getScheme()
            . '://' . $request->getUri()->getHost();
    }

    /**
     * Checks the Origin.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function checkOrigin(ServerRequestInterface $request): bool
    {
        if ($this->options['allowedOrigins'] === true) {
            // allow all '*' flag
            return true;
        }
        $origin = $request->getHeaderLine('Origin');

        if (\in_array($origin, $this->options['allowedOrigins'], true)) {
            return true;
        }

        foreach ($this->options['allowedOriginsPatterns'] as $pattern) {
            if (\preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chechs the allowed methods.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function checkMethod(ServerRequestInterface $request): bool
    {
        if ($this->options['allowedMethods'] === true) {
            // allow all '*' flag
            return true;
        }

        $requestMethod = \strtoupper($request->getHeaderLine('Access-Control-Request-Method'));

        return \in_array($requestMethod, $this->options['allowedMethods']);
    }
}
