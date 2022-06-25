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
 * Handles Content-Security-Policy HTTP Middleware.
 *
 * Provides tools for working with the Content-Security-Policy header to help defeat XSS attacks.
 *
 * @see http://www.w3.org/TR/CSP/
 * @see http://www.html5rocks.com/en/tutorials/security/content-security-policy/
 * @see http://content-security-policy.com/
 * @see https://www.owasp.org/index.php/Content_Security_Policy
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    /** @var array<string,string> */
    protected static $cpHeaders = [
        'script-src' => 'X-Script-Nonce',
        'script-src-elem' => 'X-Script-Nonce',
        'style-src' => 'X-Style-Nonce',
        'style-src-elem' => 'X-Style-Nonce',
    ];

    /** @var bool */
    protected $cspDisabled = false;

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (empty($cspHeaders = $this->getCspHeaders($response))) {
            return $response;
        }

        if ($this->cspDisabled) {
            return $this->removeCspHeaders($response);
        }

        $ruleIsSet = false;

        foreach ($cspHeaders as $header => $directives) {
            foreach (self::$cpHeaders as $type => $tokenName) {
                if ($this->authorizesInline($directives, $type)) {
                    continue;
                }

                if (!isset($cspHeaders[$header][$type])) {
                    if (null === $fallback = $this->getDirectiveFallback($directives, $type)) {
                        continue;
                    }

                    if (['\'none\''] === $fallback) {
                        // Fallback came from "default-src: 'none'"
                        // 'none' is invalid if it's not the only expression in the source list, so we leave it out
                        $fallback = [];
                    }

                    $cspHeaders[$header][$type] = $fallback;
                }

                $ruleIsSet = true;

                if (!\in_array('\'unsafe-inline\'', $cspHeaders[$header][$type], true)) {
                    $cspHeaders[$header][$type][] = '\'unsafe-inline\'';
                }

                if ($response->hasHeader($tokenName) && !$this->hasHashOrNonce($directives[$type] ?? [])) {
                    $cspHeaders[$header][$type][] = \sprintf('\'nonce-%s\'', $response->getHeaderLine($tokenName));
                }
            }
        }

        if (!$ruleIsSet) {
            return $response;
        }

        foreach ($cspHeaders as $header => $directives) {
            $response = $response->withHeader($header, $this->generateCspHeader($directives));
        }

        return $response;
    }

    /**
     * Converts a directive set array into Content-Security-Policy header.
     *
     * @param array $directives The directive set
     *
     * @return string The Content-Security-Policy header
     */
    private function generateCspHeader(array $directives): string
    {
        return \array_reduce(
            \array_keys($directives),
            static function ($res, $name) use ($directives) {
                return ('' !== $res ? $res . '; ' : '') . \sprintf('%s %s', $name, \implode(' ', $directives[$name]));
            },
            ''
        );
    }

    /**
     * Retrieves the Content-Security-Policy headers (either X-Content-Security-Policy or Content-Security-Policy) from
     * a response.
     *
     * @return array<string,array<string,array<int,string>>> An associative array of headers
     */
    private function getCspHeaders(ResponseInterface $response): array
    {
        $headers = [];

        if ($response->hasHeader('Content-Security-Policy')) {
            $headers['Content-Security-Policy'] = $this->parseDirectives($response->getHeader('Content-Security-Policy'));
        }

        if ($response->hasHeader('Content-Security-Policy-Report-Only')) {
            $headers['Content-Security-Policy-Report-Only'] = $this->parseDirectives($response->getHeader('Content-Security-Policy-Report-Only'));
        }

        if ($response->hasHeader('X-Content-Security-Policy')) {
            $headers['X-Content-Security-Policy'] = $this->parseDirectives($response->getHeader('X-Content-Security-Policy'));
        }

        return $headers;
    }

    /**
     * Converts a Content-Security-Policy header value into a directive set array.
     *
     * @param string $header The header value
     *
     * @return array<string,string[]> The directive set
     */
    private function parseDirectives(array $headers): array
    {
        $directives = [];

        if (1 == \count($headers)) {
            $headers = \explode(';', $headers[0]);
        }

        foreach ($headers as $directive) {
            $parts = \explode(' ', \trim($directive));

            if (\count($parts) < 1) {
                continue;
            }

            $name = \array_shift($parts);
            $directives[$name] = $parts;
        }

        return $directives;
    }

    /**
     * Detects if the 'unsafe-inline' is prevented for a directive within the directive set.
     *
     * @param array  $directivesSet The directive set
     * @param string $type          The name of the directive to check
     */
    private function authorizesInline(array $directivesSet, string $type): bool
    {
        if (isset($directivesSet[$type])) {
            $directives = $directivesSet[$type];
        } elseif (null === $directives = $this->getDirectiveFallback($directivesSet, $type)) {
            return false;
        }

        return \in_array('\'unsafe-inline\'', $directives, true) && !$this->hasHashOrNonce($directives);
    }

    private function hasHashOrNonce(array $directives): bool
    {
        foreach ($directives as $directive) {
            if ('\'' !== \substr($directive, -1)) {
                continue;
            }

            if ('\'nonce-' === \substr($directive, 0, 7)) {
                return true;
            }

            if (\in_array(\substr($directive, 0, 8), ['\'sha256-', '\'sha384-', '\'sha512-'], true)) {
                return true;
            }
        }

        return false;
    }

    private function getDirectiveFallback(array $directiveSet, $type)
    {
        if (\in_array($type, ['script-src-elem', 'style-src-elem'], true) || !isset($directiveSet['default-src'])) {
            // Let the browser fallback on it's own
            return null;
        }

        return $directiveSet['default-src'];
    }

    private function removeCspHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withoutHeader('X-Content-Security-Policy')
            ->withoutHeader('Content-Security-Policy')
            ->withoutHeader('Content-Security-Policy-Report-Only');
    }
}
