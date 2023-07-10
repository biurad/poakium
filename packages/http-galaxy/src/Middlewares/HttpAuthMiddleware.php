<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Middlewares;

use Biurad\Http\Interfaces\HttpAuthInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authenticates a user's identity via various HTTP authentication methods.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class HttpAuthMiddleware implements MiddlewareInterface
{
    /**
     * Authentication based on HTTP `X-Api-Key` request header.
     *
     * Supports authentication based on the access token provided in header's value.
     */
    public const HTTP_AUTH = 'X-Api-Key';

    /**
     * Http QueryParameter authentication.
     *
     * Supports authentication based on the `access_token` passed through a query parameter.
     */
    public const QUERY_AUTH = 'access_token';

    /**
     * HTTP Basic authentication based on $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW'].
     *
     * @see https://tools.ietf.org/html/rfc7617
     */
    public const BASIC_AUTH = 'Basic';

    /**
     * Authentication based on HTTP Bearer token.
     *
     * @see https://tools.ietf.org/html/rfc6750
     */
    public const BEARER_AUTH = 'Bearer';

    /**
     * URL Patterns for http authentication should be allowed on.
     *
     * E.g. an array of ['#^/home#i', '#^/secure#i'] or true for all.
     *
     * @var bool|string[]
     */
    private $urlPatterns;

    private ?HttpAuthInterface $authenticationCallback;

    private string $realm;

    /**
     * @param HttpAuthInterface $authenticationCallback For authenticating HTTP auth information
     * @param bool|string[]     $urlPatterns            E.g. an array of ['#^/home#i', '#^/secure#i'] or true for all.
     * @param string            $realm                  The HTTP authentication realm
     */
    public function __construct(HttpAuthInterface $authenticationCallback, $urlPatterns = false, string $realm = 'api')
    {
        $this->realm = $realm;
        $this->urlPatterns = $urlPatterns;
        $this->authenticationCallback = $authenticationCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $matched = $this->checkMatchingURL($request->getUri(), $request->getServerParams()['PATH_INFO'] ?? '');

        if ($matched && null !== $credentials = $this->getAuthenticationCredentials($request)) {
            $authenticated = ($this->authenticationCallback)(...$credentials);

            if (!$authenticated) {
                $response = $response->withStatus(401);

                if (self::BASIC_AUTH === $credentials[1] || self::BEARER_AUTH === $credentials[1]) {
                    $response = $response->withHeader('WWW-Authenticate', "{$credentials[1]} realm=\"{$this->realm}\"");
                }
            }
        }

        return $response;
    }

    /**
     * Obtains authentication credentials from request.
     *
     * @return array<int,mixed>|null [$token, $auth] array
     */
    private function getAuthenticationCredentials(ServerRequestInterface $request): ?array
    {
        $username = $request->getServerParams()['PHP_AUTH_USER'] ?? null;
        $password = $request->getServerParams()['PHP_AUTH_PW'] ?? null;

        if (null !== $username || null !== $password) {
            return [[$username, $password], self::BASIC_AUTH];
        }

        /*
         * Apache with php-cgi does not pass HTTP Basic authentication to PHP by default.
         * To make it work, add the following line to to your .htaccess file:
         *
         * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
         */
        if (null !== $token = $this->getTokenFromHeaders($request)) {
            if (\str_starts_with($token, self::BASIC_AUTH)) {
                return [\substr($token, 6), self::BASIC_AUTH];
            }

            if (\str_starts_with($token, self::BEARER_AUTH)) {
                return [\substr($token, 6), self::BEARER_AUTH];
            }
        }

        if ($request->hasHeader(self::HTTP_AUTH)) {
            return [$request->getHeaderLine('X-Api-Key'), self::HTTP_AUTH];
        }

        if (!empty($queryToken = $request->getQueryParams()[self::QUERY_AUTH] ?? null)) {
            return [$queryToken, self::QUERY_AUTH];
        }

        return $queryToken;
    }

    private function getTokenFromHeaders(ServerRequestInterface $request): ?string
    {
        if (!empty($header = $request->getHeaderLine('Authorization'))) {
            return $header;
        }

        return $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    }

    /**
     * Check if global basic auth is enabled for the given request.
     */
    private function checkMatchingURL(UriInterface $requestUri, string $pathInfo): bool
    {
        if (\is_bool($patterns = $this->urlPatterns)) {
            return $patterns;
        }

        if (\is_array($patterns) && !empty($patterns)) {
            $requestPath = !empty($pathInfo) ? $pathInfo : $requestUri->getPath();

            foreach ($patterns as $pathRegexp) {
                if (1 === \preg_match($pathRegexp, $requestPath)) {
                    return true;
                }
            }
        }

        return false; // No patterns match
    }
}
