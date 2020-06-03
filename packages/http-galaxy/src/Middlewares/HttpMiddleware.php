<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        $this->config = $config;
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
        $config     = $this->config;
        $policies   = $config['policies'] ?? [];
        $headers    = array_map('strval', $config['headers']['response'] ?? []);

        if (($frames = $policies['frame_policy']) === false) {
            $frames = 'DENY';
        } elseif (preg_match('#^https?:#', $policies['frame_policy'])) {
            $frames = "ALLOW-FROM $frames";
        }

        $headers['X-Frame-Options'] = $frames;
        foreach (['content_security_policy', 'csp_report_only'] as $key) {
            if (!isset($policies[$key])) {
                continue;
            }

            $value = self::buildPolicy($policies[$key]);
            $headers['Content-Security-Policy' . ($key === 'content_security_policy' ? '' : '-Report-Only')] = $value;
        }

        if (isset($config['feature_policy'])) {
            $headers['Feature-Policy'] = $this->buildPolicy($config['feature_policy']);
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
        $value = '';

		foreach ($config as $type => $policy) {
			if ($policy === false) {
				continue;
            }

			$policy = $policy === true ? [] : (array) $policy;
            $value .= $type;

			foreach ($policy as $item) {
				$value .= !isset($nonQuoted[$type]) && preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
            }

			$value .= '; ';
        }

		return $value;
	}
}
