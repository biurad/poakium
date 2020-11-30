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

use Biurad\Http\Interfaces\CookieInterface;
use Biurad\Http\Response;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait ResponseDecoratorTrait
{
    use MessageDecoratorTrait {
        getMessage as private;
    }

    /**
     * Convert response to string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string
     */
    public function __toString(): string
    {
        $eol = "\r\n"; // EOL characters used for HTTP response

        $output   = \sprintf(
            'HTTP/%s %s %s%s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase(),
            $eol
        );

        foreach ($this->getHeaders() as $name => $values) {
            $output .= \sprintf('%s: %s', $name, $this->getHeaderLine($name)) . $eol;
        }

        $output .= $eol;
        $output .= (string) $this->getBody();

        return $output;
    }

    /**
     * Returns the decorated response.
     *
     * Since the underlying Response is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        /** @var ResponseInterface $message */
        $message = $this->getMessage();

        return $message;
    }

    /**
     * Exchanges the underlying response with another.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function withResponse(ResponseInterface $response): ResponseInterface
    {
        $new          = clone $this;
        $new->message = $response;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new          = clone $this;
        $new->message = $this->getResponse()->withStatus($code, $reasonPhrase);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->getResponse()->getReasonPhrase();
    }

    /**
     * Returns the value of the Expires header as a DateTime instance.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @final
     */
    public function getExpires(): ?DateTimeInterface
    {
        try {
            return $this->getDate('Expires');
        } catch (RuntimeException $e) {
            // according to RFC 2616 invalid date formats (e.g. "0" and "-1") must be treated as in the past
            return DateTime::createFromFormat('U', \sprintf('%s', \time() - 172800));
        }
    }

    /**
     * Set a cookie on response.
     *
     * @param CookieInterface $cookie
     *
     * @return Response
     *
     * @final
     */
    public function withCookie(CookieInterface $cookie): self
    {
        $new          = clone $this;
        $new->message = $this->withHeader('Set-Cookie', (string) $cookie);

        return $new;
    }

    /**
     * Attempts to cache the sent entity by its last modification date.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param DateTimeInterface $lastModified
     * @param string            $etag
     *
     * @return Response
     */
    public function withModified(DateTimeInterface $lastModified = null, string $etag = null): self
    {
        $response = $this->getResponse();

        if ($response->hasHeader('ETag') || $response->hasHeader('Last-Modified')) {
            return $this;
        }

        if (null !== $lastModified) {
            if ($lastModified instanceof DateTime) {
                $lastModified = DateTimeImmutable::createFromMutable($lastModified);
            }

            $lastModified->setTimezone(new DateTimeZone('UTC'));
            $response = $response->withHeader('Last-Modified', $lastModified->format('D, d M Y H:i:s') . ' GMT');
        }

        if (null !== $etag) {
            $response = $response->withHeader('ETag', '"' . \addslashes($etag) . '"');
        }

        $new          = clone $this;
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
     * @return Response
     *
     * @see https://tools.ietf.org/html/rfc2616#section-10.3.5
     *
     * @final
     */
    public function withNotModified(array $headers = []): self
    {
        $response = $this->getResponse();
        $response = $response->withStatus(StatusCodeInterface::STATUS_NOT_MODIFIED);

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

        $new          = clone $this;
        $new->message = $response;

        return $new;
    }

    /**
     * Configure response to send given attachment to client.
     *
     * @param resource|StreamInterface|string $filename    local filename or stream or
     *                                                     streamable or resource
     * @param string                          $name        Public file name (in
     *                                                     attachment), by default local
     *                                                     filename. Name is mandratory
     *                                                     when filename supplied in a form
     *                                                     of stream or resource.
     * @param string                          $disposition One of "inline" or "attachment"
     * @param string                          $mimetype    returns the MIME content type of
     *                                                     a downloaded file
     *
     * @throws InvalidArgumentException
     *
     * @return Response
     */
    public function withAttachment(
        $filename,
        string $name = '',
        string $disposition = 'attachment',
        string $mimetype = 'application/octet-stream'
    ): self {
        $response = $this->getResponse();

        if (empty($name)) {
            if (!\is_string($filename)) {
                throw new InvalidArgumentException('Unable to resolve public filename');
            }

            $name = \basename($filename);
        }
        $stream = $this->getStream($filename);

        $response = $response->withHeader('Content-Type', $mimetype);
        $response = $response->withHeader('Content-Length', (string) $stream->getSize());
        $response = $response->withHeader(
            'Content-Disposition',
            $disposition . '; filename="' . \addcslashes($name, '"') . '"'
        );

        $new          = clone $this;
        $new->message = $response->withBody($stream);

        return $new;
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
     * @param null|int            $status the redirect HTTP status code
     *
     * @return Response
     */
    public function withRedirect(string $url, $status = StatusCodeInterface::STATUS_FOUND): self
    {
        $response = $this->getResponse()->withHeader('Location', (string) $url);

        if (null === $status && $this->getStatusCode() === $this->isOk()) {
            $status = StatusCodeInterface::STATUS_NOT_FOUND;
        }

        $new = clone $this;

        if ($this->isRedirection()) {
            $new->message = $response->withStatus($status);
        }

        return $new;
    }

    /**
     * Is this response empty?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return \in_array($this->getStatusCode(), [204, 205, 304], true);
    }

    /**
     * Is this response OK?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->getStatusCode() === StatusCodeInterface::STATUS_OK;
    }

    /**
     * Is this response a redirect?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return \in_array($this->getStatusCode(), [301, 302, 303, 307, 308], true);
    }

    /**
     * Is this response forbidden?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     *
     * @api
     */
    public function isForbidden(): bool
    {
        return $this->getStatusCode() === StatusCodeInterface::STATUS_FORBIDDEN;
    }

    /**
     * Is this response not Found?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->getStatusCode() === StatusCodeInterface::STATUS_NOT_FOUND;
    }

    /**
     * Is this response informational?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response successful?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response a redirection?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response a client error?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Returns true if the response may safely be kept in a shared (surrogate) cache.
     *
     * Responses marked "private" with an explicit Cache-Control directive are
     * considered uncacheable.
     *
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache
     * validator (Last-Modified, ETag) are considered uncacheable because there is
     * no way to tell when or how to remove them from the cache.
     *
     * Note that RFC 7231 and RFC 7234 possibly allow for a more permissive implementation,
     * for example "status codes that are defined as cacheable by default [...]
     * can be reused by a cache with heuristic expiration unless otherwise indicated"
     * (https://tools.ietf.org/html/rfc7231#section-6.1)
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @final
     */
    public function isCacheable(): bool
    {
        $cacheControl = \current($this->getHeader('Cache-Control'));

        if (!\in_array($this->getStatusCode(), [200, 203, 300, 301, 302, 404, 410], true)) {
            return false;
        }

        if (\strpos($cacheControl, 'no-store') !== false || \strpos($cacheControl, 'private') !== false) {
            return false;
        }

        return $this->isValidateable();
    }

    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @final
     */
    public function isValidateable(): bool
    {
        return $this->hasHeader('Last-Modified') || $this->hasHeader('ETag');
    }

    /**
     * Returns the HTTP header value converted to a date.
     *
     * @throws RuntimeException When the HTTP header is not parseable
     *
     * @return null|DateTimeInterface The parsed DateTime or the default value if the header does not exist
     */
    private function getDate(string $key, DateTime $default = null)
    {
        if (null === $value = $this->getHeaderLine($key)) {
            return $default;
        }

        if (false === $date = DateTime::createFromFormat(\DATE_RFC2822, $value)) {
            throw new RuntimeException(\sprintf('The "%s" HTTP header is not parseable (%s).', $key, $value));
        }

        return $date;
    }

    /**
     * Create stream for given filename.
     *
     * @param resource|StreamInterface|string $stream
     *
     * @return StreamInterface
     */
    private function getStream($stream): StreamInterface
    {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        if (!\is_string($stream) && !\is_resource($stream)) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        return Utils::streamFor($stream);
    }
}
