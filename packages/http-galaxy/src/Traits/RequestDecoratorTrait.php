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
     * Returns the decorated request.
     *
     * Since the underlying Request is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        /** @var RequestInterface $message */
        $message = $this->getMessage();

        return $message;
    }

    /**
     * Exchanges the underlying request with another.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    public function withRequest(RequestInterface $request): RequestInterface
    {
        $new          = clone $this;
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
    public function withRequestTarget($requestTarget): self
    {
        $new          = clone $this;
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
    public function withMethod($method): self
    {
        $new          = clone $this;
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
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withUri($uri, $preserveHost);

        return $new;
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
        $header = $this->getHeaderLine('Authorization');

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
        return \in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
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
            $this->getMethod(),
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
        return \in_array($this->getMethod(), ['GET', 'HEAD'], true);
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
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === \strtoupper($method);
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
}
