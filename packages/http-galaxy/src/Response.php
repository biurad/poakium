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

namespace Biurad\Http;

use Biurad\Http\Exception\InvalidArgumentException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Cookie as HttpFoundationCookie;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * Class Response.
 */
class Response implements ResponseInterface, StatusCodeInterface, \Stringable
{
    use Traits\MessageDecoratorTrait;

    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param resource|StreamInterface|string|null $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase (optional)
     */
    public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
    {
        if (\is_resource($body)) {
            $body = new Stream($body);
        }

        if ($body instanceof StreamInterface) {
            $body = (string) $body;
        }

        $this->message = new HttpFoundationResponse($body, $status, $headers);
        $this->message->setProtocolVersion($version);

        if (null !== $reason) {
            $this->message->setStatusCode($status, $reason);
        }
    }

    public function __destruct()
    {
        if (null !== $this->stream) {
            $this->message->setContent((string) $this->stream);
        }
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
     * Returns the decorated response.
     *
     * Since the underlying Response is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     */
    public function getResponse(): HttpFoundationResponse
    {
        return $this->message;
    }

    /**
     * Exchanges the underlying response with another.
     */
    public function withResponse(HttpFoundationResponse $response): self
    {
        $new = clone $this;
        $new->message = $response;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->message->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus(int $code, $reasonPhrase = ''): self
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(\sprintf('Response status code "%d" is not valid. It must be in 100..599 range.', $code));
        }

        $new = clone $this;
        $new->message = $this->message->setStatusCode($code, !empty($reasonPhrase) ? $reasonPhrase : null);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase(): string
    {
        return \Closure::bind(fn (HttpFoundationResponse $response) => $response->statusText, null, $this->message)($this->message);
    }

    /**
     * Set a cookie on response.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @final
     */
    public function withCookie(HttpFoundationCookie $cookie): self
    {
        $new = clone $this;
        $new->message->headers->setCookie($cookie);

        return $new;
    }

    /**
     * Attempts to cache the sent entity by its last modification date.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function withModified(\DateTimeInterface $lastModified = null, string $etag = null): self
    {
        $response = $this;

        if ($response->hasHeader('ETag') || $response->hasHeader('Last-Modified')) {
            return $this;
        }

        if (null !== $lastModified) {
            $response = $response->withHeader('Last-Modified', $lastModified->format(\DateTime::RFC7231));
        }

        if (null !== $etag) {
            $response = $response->withHeader('ETag', '"'.\addslashes($etag).'"');
        }

        $new = clone $this;
        $new->message = $response;

        return $new;
    }

    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     * This sets the status, and discards any headers that MUST NOT be included in 304 responses.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string[] $headers
     *
     * @see https://tools.ietf.org/html/rfc2616#section-10.3.5
     *
     * @final
     */
    public function withNotModified(array $headers = []): self
    {
        $response = $this;

        // remove headers that MUST NOT be included with 304 Not Modified responses
        $headers = \array_replace([
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified',
        ], $headers);

        foreach ($headers as $header) {
            $response = $response->withoutHeader($header);
        }

        $new = clone $this;
        $new->message = $response->withStatus(HttpFoundationResponse::HTTP_NOT_MODIFIED);

        return $new;
    }

    /**
     * Configure response to send given attachment to client.
     *
     * @param resource|StreamInterface|string $filename    local filename or stream or
     *                                                     streamable or resource
     * @param string                          $name        Public file name (in
     *                                                     attachment), by default local
     *                                                     filename. Name is mandatory
     *                                                     when filename supplied in a form
     *                                                     of stream or resource.
     * @param string                          $disposition One of "inline" or "attachment"
     * @param string                          $mimetype    returns the MIME content type of
     *                                                     a downloaded file
     *
     * @throws InvalidArgumentException
     */
    public function withAttachment(
        $filename,
        string $name = '',
        string $disposition = 'attachment',
        string $mimetype = 'application/octet-stream'
    ): self {
        $response = $this;

        if (empty($name)) {
            if (!\is_string($filename)) {
                throw new InvalidArgumentException('Unable to resolve public filename');
            }

            $name = \basename($filename);
        }
        $stream = $this->getStream($filename);

        $response = $response->withHeader('Content-Type', $mimetype);
        $response = $response->withHeader('Content-Length', (string) $stream->getSize());
        $response = $response->withHeader('Content-Disposition', HeaderUtils::makeDisposition($disposition, $filename, $name));

        return $response->withBody($stream);
    }

    /**
     * Redirect.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string|UriInterface $url    the redirect destination
     * @param int|null            $status the redirect HTTP status code
     */
    public function withRedirect($url, ?int $status = HttpFoundationResponse::HTTP_FOUND): self
    {
        $this->message->headers->set('Location', (string) $url);

        if (null === $status && HttpFoundationResponse::HTTP_OK === $this->getStatusCode()) {
            $status = HttpFoundationResponse::HTTP_NOT_FOUND;
        }

        $new = clone $this;

        if ($new->message->isRedirection()) {
            $new->message = $this->message->setStatusCode($status);
        }

        return $new;
    }

    /**
     * Create stream for given filename.
     *
     * @param resource|StreamInterface|string $stream
     */
    private function getStream($stream): StreamInterface
    {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        return new Stream($stream);
    }
}
