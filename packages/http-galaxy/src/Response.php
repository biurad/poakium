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

namespace BiuradPHP\Http;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use function GuzzleHttp\Psr7\stream_for;
use function in_array;
use function gmdate;
use function addslashes;
use function is_resource;
use function strtotime;
use function is_string;
use function basename;

/**
 * Class Response
 */
class Response implements ResponseInterface
{
    use Traits\ResponseDecoratorTrait;

    /**#@+
     * @const int Status codes
     */
    public const STATUS_CODE_CUSTOM = 0;
    public const STATUS_CODE_100 = 100;
    public const STATUS_CODE_101 = 101;
    public const STATUS_CODE_102 = 102;
    public const STATUS_CODE_200 = 200;
    public const STATUS_CODE_201 = 201;
    public const STATUS_CODE_202 = 202;
    public const STATUS_CODE_203 = 203;
    public const STATUS_CODE_204 = 204;
    public const STATUS_CODE_205 = 205;
    public const STATUS_CODE_206 = 206;
    public const STATUS_CODE_207 = 207;
    public const STATUS_CODE_208 = 208;
    public const STATUS_CODE_226 = 226;
    public const STATUS_CODE_300 = 300;
    public const STATUS_CODE_301 = 301;
    public const STATUS_CODE_302 = 302;
    public const STATUS_CODE_303 = 303;
    public const STATUS_CODE_304 = 304;
    public const STATUS_CODE_305 = 305;
    public const STATUS_CODE_306 = 306;
    public const STATUS_CODE_307 = 307;
    public const STATUS_CODE_308 = 308;
    public const STATUS_CODE_400 = 400;
    public const STATUS_CODE_401 = 401;
    public const STATUS_CODE_402 = 402;
    public const STATUS_CODE_403 = 403;
    public const STATUS_CODE_404 = 404;
    public const STATUS_CODE_405 = 405;
    public const STATUS_CODE_406 = 406;
    public const STATUS_CODE_407 = 407;
    public const STATUS_CODE_408 = 408;
    public const STATUS_CODE_409 = 409;
    public const STATUS_CODE_410 = 410;
    public const STATUS_CODE_411 = 411;
    public const STATUS_CODE_412 = 412;
    public const STATUS_CODE_413 = 413;
    public const STATUS_CODE_414 = 414;
    public const STATUS_CODE_415 = 415;
    public const STATUS_CODE_416 = 416;
    public const STATUS_CODE_417 = 417;
    public const STATUS_CODE_418 = 418;
    public const STATUS_CODE_422 = 422;
    public const STATUS_CODE_423 = 423;
    public const STATUS_CODE_424 = 424;
    public const STATUS_CODE_425 = 425;
    public const STATUS_CODE_426 = 426;
    public const STATUS_CODE_428 = 428;
    public const STATUS_CODE_429 = 429;
    public const STATUS_CODE_431 = 431;
    public const STATUS_CODE_451 = 451;
    public const STATUS_CODE_444 = 444;
    public const STATUS_CODE_499 = 499;
    public const STATUS_CODE_500 = 500;
    public const STATUS_CODE_501 = 501;
    public const STATUS_CODE_502 = 502;
    public const STATUS_CODE_503 = 503;
    public const STATUS_CODE_504 = 504;
    public const STATUS_CODE_505 = 505;
    public const STATUS_CODE_506 = 506;
    public const STATUS_CODE_507 = 507;
    public const STATUS_CODE_508 = 508;
    public const STATUS_CODE_510 = 510;
    public const STATUS_CODE_511 = 511;
    public const STATUS_CODE_599 = 599;
    /**#@-*/

    /**
     * @var string EOL characters used for HTTP response.
     */
    private const EOL = "\r\n";

    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param string|null|resource|StreamInterface $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase (optional)
     */
    public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
    {
        $this->message = new Psr7Response($status, $headers, $body, $version, $reason);
    }

    /**
     * Redirect.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string|UriInterface $url The redirect destination.
     * @param int|null $status The redirect HTTP status code.
     * @return static
     */
    public function withRedirect(string $url, $status = self::STATUS_CODE_302): ResponseInterface
    {
        $response = $this->getResponse()->withHeader('Location', (string) $url);

        if (null === $status && $this->getStatusCode() === $this->isOk()) {
            $status = self::STATUS_CODE_404;
        }

        $new = clone $this;
        if ($this->isRedirection()) {
            $new->message = $response->withStatus($status);
        }

        return $new;
    }

    /**
	 * Attempts to cache the sent entity by its last modification date.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
	 * @param  string|int|DateTimeInterface  $lastModified
	 */
	public function withModified($lastModified = null, string $etag = null): ResponseInterface
	{
        $response = $this->getResponse();

        if ($response->hasHeader('ETag') || $response->hasHeader('Last-Modified')) {
            return $this;
        }

        $new = clone $this;
		if ($lastModified) {
            if (is_string($lastModified)) {
                $lastModified = strtotime($lastModified);
            }

            $lastModified = is_int($lastModified)
                ? gmdate('D, d M Y H:i:s \G\M\T', $lastModified) : $lastModified->format('D, d M Y H:i:s \G\M\T');

			$response = $response->withHeader('Last-Modified', (string) $lastModified);
        }

		if ($etag) {
			$response = $response->withHeader('ETag', '"' . addslashes($etag) . '"');
        }

		return $new->message = $response->withStatus(self::STATUS_CODE_304);
    }

    /**
     * Configure response to send given attachment to client.
     *
     * @param string|StreamInterface|resource $filename                 Local filename or stream or
     *                                                                  streamable or resource.
     * @param string                                     $name          Public file name (in
     *                                                                  attachment), by default local
     *                                                                  filename. Name is mandratory
     *                                                                  when filename supplied in a form
     *                                                                  of stream or resource.
     * @param string                                     $disposition   One of "inline" or "attachment"
     * @param string                                     $mimetype      Returns the MIME content type of
     *                                                                  a downloaded file.
     *
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    public function withAttachment($filename, string $name = '', string $disposition = 'attachment', string $mimetype = 'application/octet-stream'): ResponseInterface
    {
        $response = $this->getResponse();
        if (empty($name)) {
            if (!is_string($filename)) {
                throw new InvalidArgumentException("Unable to resolve public filename");
            }

            $name = basename($filename);
        }
        $stream = $this->getStream($filename);

        $response = $response->withHeader('Content-Type', $mimetype);
        $response = $response->withHeader('Content-Length', (string) $stream->getSize());
        $response = $response->withHeader(
            'Content-Disposition', $disposition .'; filename="' . addcslashes($name, '"') . '"'
        );

        $new = clone $this;

        return $new->message = $response->withBody($stream);
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
        return \in_array($this->getResponse()->getStatusCode(), [204, 205, 304], true);
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
        return $this->getResponse()->getStatusCode() === self::STATUS_CODE_200;
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
        return \in_array($this->getResponse()->getStatusCode(), [301, 302, 303, 307, 308], true);
    }

    /**
     * Is this response forbidden?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     * @api
     */
    public function isForbidden(): bool
    {
        return $this->getResponse()->getStatusCode() === self::STATUS_CODE_403;
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
        return $this->getResponse()->getStatusCode() === self::STATUS_CODE_404;
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
        $response = $this->getResponse();

        return $response->getStatusCode() >= 100 && $response->getStatusCode() < 200;
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
        $response = $this->getResponse();

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
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
        $response = $this->getResponse();

        return $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
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
        $response = $this->getResponse();

        return $response->getStatusCode() >= 400 && $response->getStatusCode() < 500;
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
        $response = $this->getResponse();

        return $response->getStatusCode() >= 500 && $response->getStatusCode() < 600;
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
     * @final
     */
    public function isCacheable(): bool
    {
        $response = $this->getResponse();

        if (!in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 410])) {
            return false;
        }

        return $this->isValidateable();
    }

    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * @final
     */
    public function isValidateable(): bool
    {
        $response = $this->getResponse();

        return $response->hasHeader('Last-Modified') || $response->hasHeader('ETag');
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
        $response = $this->getResponse();
        $output = sprintf(
            'HTTP/%s %s %s%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            self::EOL
        );

        foreach ($response->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $response->getHeaderLine($name)) . self::EOL;
        }

        $output .= self::EOL;
        $output .= (string) $response->getBody();

        return $output;
    }

    /**
     * Create stream for given filename.
     *
     * @param string|StreamInterface|resource $stream
     *
     * @return StreamInterface
     */
    private function getStream($stream) : StreamInterface
    {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        if (! is_string($stream) && ! is_resource($stream)) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        return stream_for($stream);
    }
}
