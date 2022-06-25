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
 * Handlers response header containing POLICY in name.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class HttpPolicyMiddleware implements MiddlewareInterface
{
    /** @var array<string,mixed> */
    private $policies;

    private static $QuotedDirectives = [
        'self' => " 'self'",
        'none' => " 'none'",
        'unsafe-eval' => " 'unsafe-eval'",
        'unsafe-hashes' => " 'unsafe-hashes'",
        'unsafe-inline' => " 'unsafe-inline'",
        'strict-dynamic' => " 'strict-dynamic'",
        'report-sample' => " 'report-sample'",
        'unsafe-hashed-attributes' => " 'unsafe-hashed-attributes'",
    ];

    public function __construct(array $policies = [])
    {
        $this->policies = $policies += [
            'feature_policy' => [],
            'frame_policy' => [],
            'referrer_policy' => [],
            'content_security_policy' => null,
            'csp_report_only' => null,
            'expose_csp_nonce' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!empty($framePolicy = $this->policies['frame_policy'])) {
            if (false === $framePolicy) {
                $framePolicy = 'DENY';
            } elseif (\is_string($framePolicy) && \preg_match('#^https?:#', $framePolicy)) {
                $framePolicy = 'ALLOW-FROM ' . $framePolicy;
            }

            $response = $response->withHeader('X-Frame-Options', $framePolicy);
        }

        if (!empty($featurePolicy = $this->policies['feature_policy'])) {
            $response = $response->withHeader('Feature-Policy', $this->buildPolicy($featurePolicy));
        }

        if (!empty($referrerPolicy = $this->policies['referrer_policy'])) {
            $response = $response->withHeader('Referrer-Policy', \implode(', ', $referrerPolicy));
        }

        foreach (['content_security_policy', 'csp_report_only'] as $key) {
            $cspHeader = 'Content-Security-Policy';

            if (empty($this->policies[$key])) {
                continue;
            }

            if ('csp_report_only' === $key) {
                $cspHeader .= '-Report-Only';
            }

            $response = $response->withHeader($cspHeader, $this->buildPolicy($this->policies[$key], $nonces));

            if (true === $this->policies['expose_csp_nonce'] && !empty($nonces)) {
                if (isset($nonces['script-src']) && !$request->hasHeader('X-Script-Nonce')) {
                    $response = $response->withHeader('X-Script-Nonce', $nonces['script-src']);
                }

                if (isset($nonces['style-src']) && !$request->hasHeader('X-Style-Nonce')) {
                    $response = $response->withHeader('X-Style-Nonce', $nonces['style-src']);
                }
            }
        }

        return $response;
    }

    private function buildPolicy(array $config, ?array &$nonces = []): array
    {
        $policies = [];

        foreach ($config as $type => $policy) {
            if (false === $policy) {
                continue;
            }

            $policy = true === $policy ? [] : (array) $policy;
            $value = $type;

            foreach ($policy as $item) {
                if ('nonce-' === $item) {
                    $value .= ' \'nonce-' . ($nonces[$type] = \bin2hex(\random_bytes(18))) . '\'';
                } elseif (\in_array($item, ['sha256-', 'sha384', 'sha512-'], true)) {
                    $value .= ' \'' . $item . \base64_encode(\hash(\substr($item, 0, -1), \random_bytes(16), true)) . '\'';
                } else {
                    $value .= self::$QuotedDirectives[$item] ?? ' ' . $item;
                }
            }

            $policies[] = $value;
        }

        return $policies;
    }
}
