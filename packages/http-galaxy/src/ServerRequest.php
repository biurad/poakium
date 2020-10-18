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

namespace Biurad\Http;

use Biurad\Http\Interfaces\CookieInterface;
use Biurad\Http\Utils\IpUtils;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest
 */
class ServerRequest implements ServerRequestInterface
{
    use Traits\ServerRequestDecoratorTrait;

    public const HEADER_FORWARDED = 0b00001; // When using RFC 7239

    public const HEADER_X_FORWARDED_FOR = 0b00010;

    public const HEADER_X_FORWARDED_HOST = 0b00100;

    public const HEADER_X_FORWARDED_PROTO = 0b01000;

    public const HEADER_X_FORWARDED_PORT = 0b10000;

    public const HEADER_X_FORWARDED_ALL = 0b11110; // All "X-Forwarded-*" headers

    public const HEADER_X_FORWARDED_AWS_ELB = 0b11010; // AWS ELB doesn't send X-Forwarded-Host

    /**
     * @var array|string[]
     */
    private $trustedProxies = [];

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param null|resource|StreamInterface|string $body         Request body
     * @param string                               $version      Protocol version
     * @param array                                $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->message = new Psr7ServerRequest($method, $uri, $headers, $body, $version, $serverParams);
    }

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return ServerRequestInterface
     */
    public static function fromGlobals()
    {
        $method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers  = getallheaders();
        $uri      = Psr7ServerRequest::getUriFromGlobals();
        $body     = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = \str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'] ?? 'Http/1.1');

        $serverRequest = new static($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(Psr7ServerRequest::normalizeFiles($_FILES));
    }

    /**
     * Indicates whether this request originated from a trusted proxy.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This can be useful to determine whether or not to trust the
     * contents of a proxy-specific header.
     *
     * @return bool true if the request came from a trusted proxy, false otherwise
     */
    public function hasTrustedProxy(): bool
    {
        return $this->trustedProxies && IpUtils::checkIp($this->getRemoteAddress(), $this->trustedProxies);
    }

    /**
     * Sets a list of trusted proxies.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * @param array $proxies A list of trusted proxies, the string 'REMOTE_ADDR' will be replaced with ip address
     *
     * @throws InvalidArgumentException When $trustedHeaderSet is invalid
     */
    public function withTrustedProxies($proxies): self
    {
        $new                 = clone $this;
        $new->trustedProxies = \array_reduce($proxies, function ($proxies, $proxy) {
            if ('REMOTE_ADDR' !== $proxy) {
                $proxies[] = $proxy;
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $proxies[] = $this->getServerParam('REMOTE_ADDR');
            }

            return $proxies;
        }, []);

        return $new;
    }

    /**
     * Gets the list of trusted proxies.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return array An array of trusted proxies
     */
    public function getTrustedProxies(): array
    {
        return $this->trustedProxies;
    }

    /**
     * Get serverRequest content character set, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string
     */
    public function getContentCharset(): ?string
    {
        $mediaTypeParams = $this->getMediaTypeParams();

        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get serverRequest content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string The serverRequest content type, if known
     */
    public function getContentType(): ?string
    {
        $result = $this->getRequest()->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }

    /**
     * Set request content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return $this
     */
    public function withContentType($contentName)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withHeader('Content-Type', $contentName);

        return $new;
    }

    /**
     * Get serverRequest content length, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|int
     */
    public function getContentLength(): ?int
    {
        $result = $this->getRequest()->getHeader('Content-Length');

        return $result ? (int) $result[0] : null;
    }

    /**
     * Fetch cookie value from cookies sent by the client to the server.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key     the attribute name
     * @param mixed  $default default value to return if the attribute does not exist
     *
     * @return mixed|CookieInterface
     */
    public function getCookie($key, $default = null)
    {
        $cookies = $this->getRequest()->getCookieParams();

        if (isset($cookies[$key])) {
            return Cookie::fromString($cookies[$key]);
        }

        return $default;
    }

    /**
     * Checks if a cookie exists in the browser's request
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $name The cookie name
     *
     * @return bool
     */
    public function hasCookie($name): bool
    {
        return null !== $this->getCookie($name);
    }

    /**
     * Get serverRequest media type, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string The serverRequest media type, minus content-type params
     */
    public function getMediaType(): ?string
    {
        $contentType = $this->getContentType();

        if ($contentType) {
            $contentTypeParts = \preg_split('/\s*[;,]\s*/', $contentType);

            if ($contentTypeParts === false) {
                return null;
            }

            return \strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get serverRequest media type params, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return mixed[]
     */
    public function getMediaTypeParams(): array
    {
        $contentType       = $this->getContentType();
        $contentTypeParams = [];

        if ($contentType) {
            $contentTypeParts = \preg_split('/\s*[;,]\s*/', $contentType);

            if ($contentTypeParts !== false) {
                $contentTypePartsLength = \count($contentTypeParts);

                for ($i = 1; $i < $contentTypePartsLength; $i++) {
                    $paramParts                                     = \explode('=', $contentTypeParts[$i]);
                    $contentTypeParams[\strtolower($paramParts[0])] = $paramParts[1];
                }
            }
        }

        return $contentTypeParams;
    }

    /**
     * Fetch serverRequest parameter value from body or query string (in that order).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key     the parameter key
     * @param string $default the default value
     *
     * @return mixed the parameter value
     */
    public function getParam($key, $default = null)
    {
        $request = $this->getRequest();

        $postParams = $request->getParsedBody();
        $getParams  = $request->getQueryParams();
        $result     = $default;

        if (\is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (\is_object($postParams) && \property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Fetch associative array of body and query string parameters.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        $request = $this->getRequest();

        $params     = $request->getQueryParams();
        $postParams = $request->getParsedBody();

        if ($postParams) {
            $params = \array_merge($params, (array) $postParams);
        }

        return $params;
    }

    /**
     * Fetch parameter value from serverRequest body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getRequest()->getParsedBody();
        $result     = $default;

        if (\is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (\is_object($postParams) && \property_exists($postParams, $key)) {
            $result = $postParams->{$key};
        }

        return $result;
    }

    /**
     * Fetch parameter value from query string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQueryParam($key, $default = null)
    {
        $getParams = $this->getRequest()->getQueryParams();
        $result    = $default;

        if (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Retrieve a server parameter.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServerParam($key, $default = null)
    {
        $serverParams = $this->getRequest()->getServerParams();

        return $serverParams[$key] ?? $default;
    }

    /**
     * Get the Authorization header token from the request headers.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string
     */
    public function getAuthorizationToken($headerName = 'Bearer'): ?string
    {
        $header = $this->getRequest()->getHeaderLine('Authorization');

        if (\mb_strpos($header, "$headerName ") !== false) {
            return \mb_substr($header, 7);
        }

        return null;
    }

    /**
     * Check if request was made over http protocol.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        //Double check though attributes?
        return $this->getUri()->getScheme() == 'https';
    }

    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * It works if your JavaScript library sets an X-Requested-With HTTP header.
     * It is known to work with common JavaScript frameworks:
     *
     * @see https://wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
     *
     * @return bool true if the request is an XMLHttpRequest, false otherwise
     */
    public function isXmlHttpRequest(): bool
    {
        return \strtolower($this->getHeaderLine('X-Requested-With')) == 'xmlhttprequest';
    }

    /**
     * Get ip addr resolved from $_SERVER['REMOTE_ADDR']. Will return null if nothing if key not
     * exists. Consider using psr-15 middlewares to customize configuration.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string
     */
    public function getRemoteAddress(): ?string
    {
        return $this->getServerParam('REMOTE_ADDR');
    }

    /**
     * Checks whether or not the method is safe.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.1
     *
     * @return bool
     */
    public function isMethodSafe(): bool
    {
        return \in_array($this->getRequest()->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
    }

    /**
     * Checks whether or not the method is idempotent.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isMethodIdempotent(): bool
    {
        return \in_array(
            $this->getRequest()->getMethod(),
            ['HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE'],
            true
        );
    }

    /**
     * Checks whether the method is cacheable or not.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
     *
     * @return bool True for GET and HEAD, false otherwise
     */
    public function isMethodCacheable(): bool
    {
        return \in_array($this->getRequest()->getMethod(), ['GET', 'HEAD'], true);
    }

    /**
     * Does this serverRequest use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     *
     * @return bool
     */
    public function isMethod($method): bool
    {
        return $this->getRequest()->getMethod() === \strtoupper($method);
    }

    /**
     * Is this a DELETE serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a GET serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a HEAD serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this a PATCH serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a POST serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this an XHR serverRequest? alias of
     * "isXmlHttpRequest" method.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Generates a normalized URI for the given path,
     * Including active port on current domain.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $path A path to use instead of the current one
     *
     * @return string The normalized URI for the path
     */
    public function getUriForPath($path): string
    {
        $uri    = $this->getUri();
        $port   = $uri->getPort();
        $query  = $uri->getQuery();

        if ('' !== $query) {
            $query = '?' . $query;
        }

        if (null !== $uri->getPort() && !\in_array($uri->getPort(), [80, 443], true)) {
            $port = ':' . $uri->getPort();
        }

        return \sprintf('%s://%s%s', $uri->getScheme(), $uri->getAuthority(), $port . $path . $query);
    }
}
