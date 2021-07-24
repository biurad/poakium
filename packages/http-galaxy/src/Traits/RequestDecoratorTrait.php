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

namespace Biurad\Http\Traits;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait RequestDecoratorTrait
{
    use MessageDecoratorTrait {
        getMessage as private;
    }

    /**
     * Convert response to string.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function __toString(): string
    {
        $eol = "\r\n"; // EOL characters used for HTTP response
        $output = \sprintf('%s %s HTTP/%s', $this->getMethod(), (string) $this->getUri(), $this->getProtocolVersion() . $eol);

        foreach ($this->getHeaders() as $name => $values) {
            if ('Host' === $name) {
                $values = \array_unique($values);
            }

            if (\count($values) > 10) {
                $output .= \sprintf('%s: %s', $name, $this->getHeaderLine($name)) . $eol;
            } else {
                foreach ($values as $value) {
                    $output .= $name . ': ' . $value . $eol;
                }
            }
        }

        $output .= $eol . (string) $this->getBody();

        return $output;
    }

    /**
     * Returns the decorated request.
     *
     * Since the underlying Request is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     */
    public function getRequest(): RequestInterface
    {
        return $this->getMessage();
    }

    /**
     * Exchanges the underlying request with another.
     */
    public function withRequest(RequestInterface $request): RequestInterface
    {
        $new = clone $this;
        $new->message = $request;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget(): string
    {
        return $this->getRequest()->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget): RequestInterface
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withRequestTarget($requestTarget);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method): RequestInterface
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withMethod($method);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->getRequest()->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withUri($uri, $preserveHost);

        return $new;
    }

    /**
     * Get the Authorization header token from the request headers.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function getAuthorizationToken(string $headerName = 'Bearer'): ?string
    {
        $header = $this->getHeaderLine('Authorization');

        if (false !== \mb_strpos($header, "$headerName ")) {
            return \mb_substr($header, 7);
        }

        return null;
    }

    /**
     * Check if request was made over http protocol.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function isSecure(): bool
    {
        return 'https' == $this->getUri()->getScheme();
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
        return 'xmlhttprequest' == \strtolower($this->getHeaderLine('X-Requested-With'));
    }

    /**
     * Checks whether or not the method is safe.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.1
     */
    public function isMethodSafe(): bool
    {
        return \in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
    }

    /**
     * Checks whether or not the method is idempotent.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function isMethodIdempotent(): bool
    {
        return \in_array($this->getMethod(), ['HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE'], true);
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
        return \in_array($this->getMethod(), ['GET', 'HEAD'], true);
    }

    /**
     * Does this serverRequest use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === \strtoupper($method);
    }

    /**
     * Is this an XHR serverRequest? alias of "isXmlHttpRequest" method.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @see isXmlHttpRequest method
     */
    public function isXhr(): bool
    {
        return $this->isXmlHttpRequest();
    }
}
