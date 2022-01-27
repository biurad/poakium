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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class Request implements RequestInterface, \Stringable
{
    use Traits\MessageDecoratorTrait;

    /** @var string|null */
    private $requestTarget;

    /** @var UriInterface|null */
    private $uri;

    /**
     * @param string                               $method  HTTP method
     * @param string|UriInterface                  $uri     URI
     * @param array                                $headers Request headers
     * @param resource|StreamInterface|string|null $body    Request body
     * @param string                               $version Protocol version
     */
    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1')
    {
        if ($body instanceof StreamInterface) {
            $body = $body->detach();
        }

        $this->message = HttpFoundationRequest::create((string) $uri, $method, [], [], [], $headers + ['SERVER_PROTOCOL' => 'HTTP/' . $version], $body, $version);
    }

    /**
     * Convert response to string.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function __toString(): string
    {
        return $this->message->__toString();
    }

    /**
     * Returns the decorated request.
     *
     * Since the underlying Request is immutable as well, exposing it is not an issue.
     */
    public function getRequest(): HttpFoundationRequest
    {
        return $this->message;
    }

    /**
     * Exchanges the underlying request with another.
     *
     * @return static
     */
    public function withRequest(HttpFoundationRequest $request): self
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
        return $this->requestTarget ?? $this->message->getRequestUri();
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withRequestTarget($requestTarget): self
    {
        if (\preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->message->getMethod();
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withMethod($method): self
    {
        $new = clone $this;
        $new->message->setMethod($method);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->uri ?? $this->uri = new Uri($this->message->getSchemeAndHttpHost() . $this->message->getRequestUri());
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri;

        $new->message = \Closure::bind(static function (HttpFoundationRequest $request) use ($uri, $preserveHost): HttpFoundationRequest {
            $request->requestUri = (false !== $pos = \strpos((string) $uri, '//')) ? \substr((string) $uri, $pos) : (string) $uri;

            if ('' !== $uriQuery = $uri->getQuery()) {
                $request->server->set('QUERY_STRING', $request::normalizeQueryString($uriQuery));
            }

            if ('https' === $uri->getScheme()) {
                $request->server->set('HTTPS', 'on');
            }

            if (!$preserveHost || !$request->headers->has('Host')) {
                if ('' === $requestHost = $uri->getHost()) {
                    return $request;
                }

                if ('' !== $uri->getScheme()) {
                    $requestHost = $uri->getScheme() . '://' . $requestHost;
                }

                if (!\in_array($uri->getPort(), [null, '', 80, 443], true)) {
                    $requestHost .= ':' . $uri->getPort();
                }

                $request->headers->set('HOST', $requestHost);
            }

            return $request;
        }, null, $this->message)($this->message);

        return $new;
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
}
