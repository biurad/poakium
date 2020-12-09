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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Handles Default Settings for Request and Response
 *
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
class HttpMiddleware implements MiddlewareInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $this->normalizeConfig($config);
    }

    /**
     * {@inheritDoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // Append headers to request headers...
        $this->appendRequestHeaders($request, $this->config['headers']['request'] ?? []);

        $response = $handler->handle($request);
        $this->resolveResponse($response, $this->config);

        return $response;
    }

    private function appendRequestHeaders(Request &$request, array $headers): void
    {
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
    }

    private function resolveResponse(ResponseInterface &$response, array $config): void
    {
        $policies   = $config['policies'] ?? [];
        $headers    = \array_map('strval', $config['headers']['response'] ?? []);

        if (null !== $frames = $policies['frame_policy']) {
            if (\preg_match('#^https?:#', $policies['frame_policy'])) {
                $frames = "ALLOW-FROM $frames";
            }

            $headers['X-Frame-Options'] = false === $frames ? 'DENY' : $frames;
        }

        foreach (['content_security_policy', 'csp_report_only'] as $key) {
            $cpKey = ($key === 'content_security_policy' ? '' : '-Report-Only');

            if (!isset($policies[$key])) {
                continue;
            }

            $headers['Content-Security-Policy' . $cpKey] = $value = $this->buildPolicy($policies[$key]);
        }

        if (isset($config['feature_policy'])) {
            $headers['Feature-Policy'] = $this->buildPolicy($config['feature_policy']);
        }

        if (isset($config['referrer_policy'])) {
            $headers['Referrer-Policy'] = \implode(', ', $config['referrer_policy']);
        }

        foreach ($headers as $key => $value) {
            if ($value !== '') {
                $response = $response->withHeader($key, $value);
            }
        }
    }

    private function buildPolicy(array $config): string
    {
        $nonQuoted = ['require-sri-for' => 1, 'sandbox' => 1];
        $value     = '';

        foreach ($config as $type => $policy) {
            if ($policy === false) {
                continue;
            }

            $policy = $policy === true ? [] : (array) $policy;
            $value .= $type;

            foreach ($policy as $item) {
                $value .= !isset($nonQuoted[$type]) && \preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
            }

            $value .= '; ';
        }

        return $value;
    }

    /**
     * Normalize the options for usage
     *
     * @param array $options
     *
     * @return array
     */
    private function normalizeConfig(array $options = []): array
    {
        $options = \array_merge([
            'headers'      => ['response' => [], 'request' => []],
            'policies'     => [
                'frame_policy' => null,
            ],
        ], $options);

        return $options;
    }
}
