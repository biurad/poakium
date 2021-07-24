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

namespace Biurad\Http\Factory;

use Biurad\Http\Response;
use Biurad\Http\ServerRequest as Request;
use Biurad\Http\ServerRequest;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\RelativeStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Psr7Bridge
{
    private const CR = "\r";

    private const LF = "\n";

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * Serialize a response message to an array.
     */
    public static function responseToArray(ResponseInterface $response): array
    {
        return [
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'protocol_version' => $response->getProtocolVersion(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
        ];
    }

    /**
     * Serialize a request message to an array.
     */
    public static function requestToArray(RequestInterface $request): array
    {
        return [
            'method' => $request->getMethod(),
            'request_target' => $request->getRequestTarget(),
            'uri' => (string) $request->getUri(),
            'protocol_version' => $request->getProtocolVersion(),
            'headers' => $request->getHeaders(),
            'body' => (string) $request->getBody(),
        ];
    }

    /**
     * Serialize a request message to an array.
     */
    public static function serverRequestToArray(ServerRequestInterface $request): array
    {
        return self::requestToArray($request) + [
            'attributes' => $request->getAttributes(),
            'cookie_params' => $request->getCookieParams(),
            'server_params' => $request->getServerParams(),
            'uploaded_files' => $request->getUploadedFiles(),
            'parsed_body' => $request->getParsedBody(),
        ];
    }

    /**
     * Deserialize a response array to a response instance.
     *
     * @throws \UnexpectedValueException when cannot deserialize response
     */
    public function responseFromArray(array $serializedResponse): Response
    {
        try {
            $body = $this->streamFactory->createStream(self::getValueFromKey($serializedResponse, 'body'));

            $statusCode = self::getValueFromKey($serializedResponse, 'status_code');
            $headers = self::getValueFromKey($serializedResponse, 'headers');
            $protocolVersion = self::getValueFromKey($serializedResponse, 'protocol_version');
            $reasonPhrase = self::getValueFromKey($serializedResponse, 'reason_phrase');

            return new Response($statusCode, $headers, $body, $protocolVersion, $reasonPhrase);
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException('Cannot deserialize response', $exception->getCode(), $exception);
        }
    }

    /**
     * Parse a response from a string.
     *
     * @throws \UnexpectedValueException when errors occur parsing the message
     */
    public function responseFromString(string $response): Response
    {
        \fwrite($resource = \fopen('php://temp', 'r+'), $response);

        $stream = $this->streamFactory->createStreamFromResource($resource);
        $stream->rewind();

        [$version, $status, $reasonPhrase] = self::getStatusLine($stream);
        [$headers, $body] = self::splitStream($stream);

        return new Response($status, $headers, $body, $version, $reasonPhrase);
    }

    /**
     * Deserialize a request array to a request instance.
     *
     * @throws \UnexpectedValueException when cannot deserialize response
     */
    public function requestFromArray(array $serializedRequest): Request
    {
        try {
            $uri = self::getValueFromKey($serializedRequest, 'uri');
            $method = self::getValueFromKey($serializedRequest, 'method');
            $body = $this->streamFactory->createStream(self::getValueFromKey($serializedRequest, 'body'));
            $headers = self::getValueFromKey($serializedRequest, 'headers');
            $requestTarget = self::getValueFromKey($serializedRequest, 'request_target');
            $protocolVersion = self::getValueFromKey($serializedRequest, 'protocol_version');

            $request = (new Request($method, $uri, $headers, $body, $protocolVersion, $serializedRequest['server_params'] ?? $_SERVER));
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException('Cannot deserialize request', $exception->getCode(), $exception);
        }

        if (isset($serializedRequest['attributes'])) {
            foreach ($serializedRequest['attributes'] as $attrKey => $attrValue) {
                $request = $request->withAttribute($attrKey, $attrValue);
            }
        }

        if (isset($serializedRequest['cookie_params'])) {
            $request = $request->withCookieParams($serializedRequest['cookie_params']);
        }

        if (isset($serializedRequest['uploaded_files'])) {
            $request = $request->withUploadedFiles($serializedRequest['uploaded_files']);
        }

        if (isset($serializedRequest['parsed_body'])) {
            $request = $request->withParsedBody($serializedRequest['parsed_body']);
        }

        return $request->withRequestTarget($requestTarget);
    }

    /**
     * Parse a server request from a string.
     *
     * @throws \UnexpectedValueException when errors occur parsing the message
     */
    public function requestFromString(string $request): ServerRequest
    {
        \fwrite($resource = \fopen('php://temp', 'r+'), $request);

        $stream = $this->streamFactory->createStreamFromResource($resource);
        $stream->rewind();

        [$method, $requestTarget, $version] = self::getRequestLine($stream);
        $uri = self::createUriFromRequestTarget($requestTarget);

        [$headers, $body] = self::splitStream($stream);

        return (new Request($method, $uri, $headers, $body, $version, $_SERVER))->withRequestTarget($requestTarget);
    }

    /**
     * Retrieve a single line from the stream.
     *
     * Retrieves a line from the stream; a line is defined as a sequence of
     * characters ending in a CRLF sequence.
     *
     * @throws \UnexpectedValueException if the sequence contains a CR
     *                                   or LF in isolation, or ends in a CR
     */
    protected static function getLine(StreamInterface $stream): string
    {
        $line = '';
        $crFound = false;

        while (!$stream->eof()) {
            $char = $stream->read(1);

            if ($crFound && self::LF === $char) {
                $crFound = false;

                break;
            }

            // CR NOT followed by LF
            if ($crFound && self::LF !== $char) {
                throw new \UnexpectedValueException('Unexpected carriage return detected');
            }

            // LF in isolation
            if (!$crFound && self::LF === $char) {
                throw new \UnexpectedValueException('Unexpected line feed detected');
            }

            // CR found; do not append
            if (self::CR === $char) {
                $crFound = true;

                continue;
            }

            // Any other character: append
            $line .= $char;
        }

        // CR found at end of stream
        if ($crFound) {
            throw new \UnexpectedValueException('Unexpected end of headers');
        }

        return $line;
    }

    /**
     * Split the stream into headers and body content.
     *
     * Returns an array containing two elements
     *
     * - The first is an array of headers
     * - The second is a StreamInterface containing the body content
     *
     * @throws \UnexpectedValueException for invalid headers
     */
    protected static function splitStream(StreamInterface $stream): array
    {
        $headers = [];
        $currentHeader = false;

        while ($line = self::getLine($stream)) {
            if (\preg_match(';^(?P<name>[!#$%&\'*+.^_`\|~0-9a-zA-Z-]+):(?P<value>.*)$;', $line, $matches)) {
                $currentHeader = $matches['name'];

                if (!isset($headers[$currentHeader])) {
                    $headers[$currentHeader] = [];
                }
                $headers[$currentHeader][] = \ltrim($matches['value']);

                continue;
            }

            if (!$currentHeader) {
                throw new \UnexpectedValueException('Invalid header detected');
            }

            if (!\preg_match('#^[ \t]#', $line)) {
                throw new \UnexpectedValueException('Invalid header continuation');
            }

            // Append continuation to last header value found
            $value = \array_pop($headers[$currentHeader]);
            $headers[$currentHeader][] = $value . \ltrim($line);
        }

        // use a limiting stream to avoid copying initial stream into memory
        return [$headers, \class_exists(RelativeStream::class) ? new RelativeStream($stream, $stream->tell()) : new LimitStream($stream, -1, $stream->tell())];
    }

    /**
     * Retrieve the components of the request line.
     *
     * Retrieves the first line of the stream and parses it, raising an
     * exception if it does not follow specifications; if valid, returns a list
     * with the method, target, and version, in that order.
     *
     * @throws \UnexpectedValueException
     */
    private static function getRequestLine(StreamInterface $stream): array
    {
        \preg_match(
            '#^(?P<method>[!\#$%&\'*+.^_`|~a-zA-Z0-9-]+) (?P<target>[^\s]+) HTTP/(?P<version>[1-9]\d*\.\d+)$#',
            self::getLine($stream),
            $matches
        );

        if (empty($matches)) {
            throw new \UnexpectedValueException('Invalid request line detected');
        }

        return [$matches['method'], $matches['target'], $matches['version']];
    }

    /**
     * Create and return a Uri instance based on the provided request target.
     *
     * If the request target is of authority or asterisk form, an empty Uri
     * instance is returned; otherwise, the value is used to create and return
     * a new Uri instance.
     */
    private static function createUriFromRequestTarget(string $requestTarget): Uri
    {
        if (\preg_match('#^https?://#', $requestTarget)) {
            return new Uri($requestTarget);
        }

        if (\preg_match('#^(\*|[^/])#', $requestTarget)) {
            return new Uri();
        }

        return new Uri($requestTarget);
    }

    /**
     * @param string $message
     *
     * @throws \UnexpectedValueException
     *
     * @return mixed
     */
    private static function getValueFromKey(array $data, string $key, string $message = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        throw new \UnexpectedValueException($message ?? \sprintf('Missing "%s" key in serialized response', $key));
    }

    /**
     * Retrieve the status line for the message.
     *
     * @throws \UnexpectedValueException if line is malformed
     *
     * @return array Array with three elements: 0 => version, 1 => status, 2 => reason
     */
    private static function getStatusLine(StreamInterface $stream): array
    {
        \preg_match(
            '#^HTTP/(?P<version>[1-9]\d*\.\d) (?P<status>[1-5]\d{2})(\s+(?P<reason>.+))?$#',
            self::getLine($stream),
            $matches
        );

        if (empty($matches)) {
            throw new \UnexpectedValueException('No status line detected');
        }

        return [$matches['version'], (int) $matches['status'], $matches['reason'] ?? ''];
    }
}
