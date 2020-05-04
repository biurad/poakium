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

namespace BiuradPHP\Http\Csp;

use Psr\Http\Message\ResponseInterface;
use BiuradPHP\Http\Interfaces\CspInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles Content-Security-Policy HTTP header.
 *
 * Provides tools for working with the Content-Security-Policy header
 * to help defeat XSS attacks.
 *
 * @see     http://www.w3.org/TR/CSP/
 * @see     http://www.html5rocks.com/en/tutorials/security/content-security-policy/
 * @see     http://content-security-policy.com/
 * @see     https://www.owasp.org/index.php/Content_Security_Policy
 *
 * @author  Romain Neutron <imprec@gmail.com>
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 *
 * @internal
 */
class ContentSecurityPolicy implements CspInterface
{
    private $nonceGenerator;
    private $cspDisabled = false;

    public function __construct(NonceGenerator $nonceGenerator)
    {
        $this->nonceGenerator = $nonceGenerator;
    }

    /**
     * Returns an array of nonces and Content-Security-Policy headers.
     *
     * Nonce can be provided by;
     *  - The request - In case HTML content is fetched via AJAX and inserted in DOM, it must use the same nonce as origin
     *  - The response -  A call to getNonces() has already been done previously. Same nonce are returned
     *  - They are otherwise randomly generated
     *
     * @return array
     */
    public function getNonces(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->hasHeader('X-Script-Nonce') && $request->hasHeader('X-Style-Nonce')) {
            return [
                'csp_script_nonce' => $request->getHeaderLine('X-Script-Nonce'),
                'csp_style_nonce' => $request->getHeaderLine('X-Style-Nonce'),
            ];
        }

        if ($response->hasHeader('X-Script-Nonce') && $response->hasHeader('X-Style-Nonce')) {
            return [
                'csp_script_nonce' => $response->getHeaderLine('X-Script-Nonce'),
                'csp_style_nonce' => $response->getHeaderLine('X-Style-Nonce'),
            ];
        }

        $nonces = [
            'csp_script_nonce' => $this->generateNonce(),
            'csp_style_nonce' => $this->generateNonce(),
        ];

        $response = $response->withAddedHeader('X-Script-Nonce', $nonces['csp_script_nonce'])
            ->withAddedHeader('X-Style-Nonce', $nonces['csp_style_nonce']);

        return $nonces;
    }

    /**
     * Disables Content-Security-Policy.
     *
     * All related headers will be removed.
     */
    public function disableCsp()
    {
        $this->cspDisabled = true;
    }

    /**
     * Cleanup temporary headers and updates Content-Security-Policy headers.
     *
     * @return array Nonces used by the bundle in Content-Security-Policy header
     */
    public function updateResponseHeaders(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->cspDisabled) {
            $response = $this->removeCspHeaders($response);

            return [];
        }

        $nonces = $this->getNonces($request, $response);
        $response = $this->cleanHeaders($response);
        [$respond, $nonces] = $this->updateCspHeaders($response, $nonces);
        $response = $respond;

        $this->emitHeaders($response);

        return $nonces;
    }

    private function cleanHeaders(ResponseInterface $response)
    {
        $response = $response
            ->withoutHeader('X-Script-Nonce')
            ->withoutHeader('X-Style-Nonce');

        return $response;
    }

    private function removeCspHeaders(ResponseInterface $response)
    {
        $response = $response
            ->withoutHeader('X-Content-Security-Policy')
            ->withoutHeader('Content-Security-Policy')
            ->withoutHeader('Content-Security-Policy-Report-Only');

        return $response;
    }

    private function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $header => $values) {
            $name  = ucwords($header, '-');

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }
    }

    /**
     * Updates Content-Security-Policy headers in a response.
     *
     * @return array
     */
    private function updateCspHeaders(ResponseInterface $response, array $nonces = [])
    {
        $nonces = array_replace([
            'csp_script_nonce' => $this->generateNonce(),
            'csp_style_nonce' => $this->generateNonce(),
        ], $nonces);

        $ruleIsSet = false;

        $headers = $this->getCspHeaders($response);

        foreach ($headers as $header => $directives) {
            foreach (['script-src' => 'csp_script_nonce', 'style-src' => 'csp_style_nonce'] as $type => $tokenName) {
                if ($this->authorizesInline($directives, $type)) {
                    continue;
                }

                if (!isset($headers[$header][$type])) {
                    if (isset($headers[$header]['default-src'])) {
                        $headers[$header][$type] = $headers[$header]['default-src'];
                    } else {
                        // If there is no script-src/style-src and no default-src, no additional rules required.
                        continue;
                    }
                }

                $ruleIsSet = true;


                if (!\in_array('\'unsafe-inline\'', $headers[$header][$type], true)) {
                    $headers[$header][$type][] = '\'unsafe-inline\'';
                }
                $headers[$header][$type][] = sprintf('\'nonce-%s\'', $nonces[$tokenName]);
            }
        }

        if (isset($headers['Content-Security-Policy'])) {
            $values = $this->generateCspHeader($headers['Content-Security-Policy']);

            if (false !== strpos($values, 'csp_script_nonce')) {
				$values = str_replace(
                    'csp_script_nonce', 'nonce-' . $nonces['csp_script_nonce'], $values
                );
            }

            if (false !== strpos($values, 'csp_style_nonce')) {
				$values = str_replace(
                    'csp_style_nonce', 'nonce-' . $nonces['csp_style_nonce'], $values
                );
            }

            $response = $response->withHeader('Content-Security-Policy', $values);
        }

        return [$response, $nonces];
    }

    /**
     * Generates a valid Content-Security-Policy nonce.
     *
     * @return string
     */
    private function generateNonce()
    {
        return $this->nonceGenerator->generate();
    }

    /**
     * Converts a directive set array into Content-Security-Policy header.
     *
     * @param array $directives The directive set
     *
     * @return string The Content-Security-Policy header
     */
    private function generateCspHeader(array $directives)
    {
        return array_reduce(array_keys($directives), function ($res, $name) use ($directives) {
            return ('' !== $res ? $res.'; ' : '').sprintf('%s %s', $name, implode(' ', $directives[$name]));
        }, '');
    }

    /**
     * Converts a Content-Security-Policy header value into a directive set array.
     *
     * @param string $header The header value
     *
     * @return array The directive set
     */
    private function parseDirectives($header)
    {
        $directives = [];

        foreach (explode(';', $header) as $directive) {
            $parts = explode(' ', trim($directive));
            if (\count($parts) < 1) {
                continue;
            }
            $name = array_shift($parts);
            $directives[$name] = $parts;
        }

        return $directives;
    }

    /**
     * Detects if the 'unsafe-inline' is prevented for a directive within the directive set.
     *
     * @param array  $directivesSet The directive set
     * @param string $type          The name of the directive to check
     *
     * @return bool
     */
    private function authorizesInline(array $directivesSet, $type)
    {
        if (isset($directivesSet[$type])) {
            $directives = $directivesSet[$type];
        } elseif (isset($directivesSet['default-src'])) {
            $directives = $directivesSet['default-src'];
        } else {
            return false;
        }

        return \in_array('\'unsafe-inline\'', $directives, true) && !$this->hasHashOrNonce($directives);
    }

    private function hasHashOrNonce(array $directives)
    {
        foreach ($directives as $directive) {
            if ('\'' !== substr($directive, -1)) {
                continue;
            }

            if ('\'nonce-' === substr($directive, 0, 7)) {
                return true;
            }

            if (\in_array(substr($directive, 0, 8), ['\'sha256-', '\'sha384-', '\'sha512-'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the Content-Security-Policy headers (either X-Content-Security-Policy or Content-Security-Policy) from
     * a response.
     *
     * @return array An associative array of headers
     */
    private function getCspHeaders(ResponseInterface $response)
    {
        $headers = [];

        if ($response->hasHeader('Content-Security-Policy')) {
            $headers['Content-Security-Policy'] = $this->parseDirectives($response->getHeaderLine('Content-Security-Policy'));
        }

        if ($response->hasHeader('Content-Security-Policy-Report-Only')) {
            $headers['Content-Security-Policy-Report-Only'] = $this->parseDirectives($response->getHeaderLine('Content-Security-Policy-Report-Only'));
        }

        if ($response->hasHeader('X-Content-Security-Policy')) {
            $headers['X-Content-Security-Policy'] = $this->parseDirectives($response->getHeaderLine('X-Content-Security-Policy'));
        }

        return $headers;
    }
}
