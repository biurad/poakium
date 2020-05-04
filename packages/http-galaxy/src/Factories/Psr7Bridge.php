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

namespace BiuradPHP\Http\Factories;

use BiuradPHP\Http\Response;
use BiuradPHP\Http\Stream;
use GuzzleHttp\Exception;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use BiuradPHP\Http\ServerRequest as Request;

use function fopen;
use function array_pop;
use function ltrim;
use function preg_match;
use function sprintf;

/**
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Psr7Bridge
{
    private const CR  = "\r";
    private const LF  = "\n";

    /**
     * Serialize a response message to an array.
     */
    public static function responseToArray(ResponseInterface $response) : array
    {
        return [
            'status_code'      => $response->getStatusCode(),
            'reason_phrase'    => $response->getReasonPhrase(),
            'protocol_version' => $response->getProtocolVersion(),
            'headers'          => $response->getHeaders(),
            'body'             => (string) $response->getBody(),
        ];
    }

    /**
     * Deserialize a response array to a response instance.
     *
     * @throws \UnexpectedValueException when cannot deserialize response
     */
    public static function responseFromArray(array $serializedResponse) : Response
    {
        try {
            $body = new Stream(fopen('php://memory', 'wb+'));
            $body->write(self::getValueFromKey($serializedResponse, 'body'));

            $statusCode      = self::getValueFromKey($serializedResponse, 'status_code');
            $headers         = self::getValueFromKey($serializedResponse, 'headers');
            $protocolVersion = self::getValueFromKey($serializedResponse, 'protocol_version');
            $reasonPhrase    = self::getValueFromKey($serializedResponse, 'reason_phrase');

            return new Response($statusCode, $headers, $body, $protocolVersion, $reasonPhrase);
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException('Cannot deserialize response', $exception->getCode(), $exception);
        }
    }

    /**
     * Parse a response from a stream.
     *
     * @throws Exception\InvalidArgumentException when the stream is not readable.
     * @throws \UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromStreamToResponse(StreamInterface $stream) : Response
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new Exception\InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        [$version, $status, $reasonPhrase] = self::getStatusLine($stream);
        [$headers, $body]                  = self::splitStream($stream);

        return new Response($body, $status, $headers, $version, $reasonPhrase);
    }

    /**
     * Serialize a request message to an array.
     */
    public static function requestToArray(RequestInterface $request) : array
    {
        return [
            'method'           => $request->getMethod(),
            'request_target'   => $request->getRequestTarget(),
            'uri'              => (string) $request->getUri(),
            'protocol_version' => $request->getProtocolVersion(),
            'headers'          => $request->getHeaders(),
            'body'             => (string) $request->getBody(),
        ];
    }

    /**
     * Deserialize a request array to a request instance.
     *
     * @throws \UnexpectedValueException when cannot deserialize response
     */
    public static function requestFromArray(array $serializedRequest) : Request
    {
        try {
            $uri             = self::getValueFromKey($serializedRequest, 'uri');
            $method          = self::getValueFromKey($serializedRequest, 'method');
            $body            = new Stream(fopen('php://memory', 'wb+'));
            $body->write(self::getValueFromKey($serializedRequest, 'body'));
            $headers         = self::getValueFromKey($serializedRequest, 'headers');
            $requestTarget   = self::getValueFromKey($serializedRequest, 'request_target');
            $protocolVersion = self::getValueFromKey($serializedRequest, 'protocol_version');

            return (new Request($method, $uri, $headers, $body, $protocolVersion))
                ->withRequestTarget($requestTarget);
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException('Cannot deserialize request', $exception->getCode(), $exception);
        }
    }

    /**
     * Deserialize a request stream to a request instance.
     *
     * @throws Exception\InvalidArgumentException if the message stream is not
     *     readable or seekable.
     * @throws \UnexpectedValueException if an invalid request line is detected.
     */
    public static function fromStreamToRequest(StreamInterface $stream) : Request
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new Exception\InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        [$method, $requestTarget, $version] = self::getRequestLine($stream);
        $uri = self::createUriFromRequestTarget($requestTarget);

        [$headers, $body] = self::splitStream($stream);

        return (new Request($method, $uri, $headers, $body, $version))
            ->withRequestTarget($requestTarget);
    }

    /**
     * Retrieve a single line from the stream.
     *
     * Retrieves a line from the stream; a line is defined as a sequence of
     * characters ending in a CRLF sequence.
     *
     * @throws \UnexpectedValueException if the sequence contains a CR
     *     or LF in isolation, or ends in a CR.
     */
    protected static function getLine(StreamInterface $stream) : string
    {
        $line    = '';
        $crFound = false;
        while (! $stream->eof()) {
            $char = $stream->read(1);

            if ($crFound && $char === self::LF) {
                $crFound = false;
                break;
            }

            // CR NOT followed by LF
            if ($crFound && $char !== self::LF) {
                throw new \UnexpectedValueException('Unexpected carriage return detected');
            }

            // LF in isolation
            if (! $crFound && $char === self::LF) {
                throw new \UnexpectedValueException('Unexpected line feed detected');
            }

            // CR found; do not append
            if ($char === self::CR) {
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
     * @throws Exception\DeserializationException For invalid headers.
     */
    protected static function splitStream(StreamInterface $stream) : array
    {
        $headers       = [];
        $currentHeader = false;

        while ($line = self::getLine($stream)) {
            if (preg_match(';^(?P<name>[!#$%&\'*+.^_`\|~0-9a-zA-Z-]+):(?P<value>.*)$;', $line, $matches)) {
                $currentHeader = $matches['name'];
                if (! isset($headers[$currentHeader])) {
                    $headers[$currentHeader] = [];
                }
                $headers[$currentHeader][] = ltrim($matches['value']);
                continue;
            }

            if (! $currentHeader) {
                throw new \UnexpectedValueException('Invalid header detected');
            }

            if (! preg_match('#^[ \t]#', $line)) {
                throw new \UnexpectedValueException('Invalid header continuation');
            }

            // Append continuation to last header value found
            $value = array_pop($headers[$currentHeader]);
            $headers[$currentHeader][] = $value . ltrim($line);
        }

        // use RelativeStream to avoid copying initial stream into memory
        return [$headers, new LimitStream($stream, -1, $stream->tell())];
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
    private static function getRequestLine(StreamInterface $stream) : array
    {
        $requestLine = self::getLine($stream);

        if (! preg_match(
            '#^(?P<method>[!\#$%&\'*+.^_`|~a-zA-Z0-9-]+) (?P<target>[^\s]+) HTTP/(?P<version>[1-9]\d*\.\d+)$#',
            $requestLine,
            $matches
        )) {
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
    private static function createUriFromRequestTarget(string $requestTarget) : Uri
    {
        if (preg_match('#^https?://#', $requestTarget)) {
            return new Uri($requestTarget);
        }

        if (preg_match('#^(\*|[^/])#', $requestTarget)) {
            return new Uri();
        }

        return new Uri($requestTarget);
    }

    /**
     * @param array $data
     * @param string $key
     * @param string $message
     * @return mixed
     * @throws \UnexpectedValueException
     */
    private static function getValueFromKey(array $data, string $key, string $message = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        if ($message === null) {
            $message = sprintf('Missing "%s" key in serialized response', $key);
        }
        throw new \UnexpectedValueException($message);
    }

    /**
     * Retrieve the status line for the message.
     *
     * @return array Array with three elements: 0 => version, 1 => status, 2 => reason
     * @throws \UnexpectedValueException if line is malformed
     */
    private static function getStatusLine(StreamInterface $stream) : array
    {
        $line = self::getLine($stream);

        if (! preg_match(
            '#^HTTP/(?P<version>[1-9]\d*\.\d) (?P<status>[1-5]\d{2})(\s+(?P<reason>.+))?$#',
            $line,
            $matches
        )) {
            throw new \UnexpectedValueException('No status line detected');
        }

        return [$matches['version'], (int) $matches['status'], isset($matches['reason']) ? $matches['reason'] : ''];
    }
}
