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

use Biurad\Http\Interfaces\Psr17Interface;
use Biurad\Http\Request;
use Biurad\Http\Response;
use Biurad\Http\ServerRequest;
use Biurad\Http\Stream;
use Biurad\Http\UploadedFile;
use Biurad\Http\Uri;
use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class GuzzleHttpPsr7Factory implements Psr17Interface
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = 'Ok'): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (empty($method)) {
            if (!empty($serverParams['REQUEST_METHOD'])) {
                $method = $serverParams['REQUEST_METHOD'];
            } else {
                throw new \InvalidArgumentException('Cannot determine HTTP method');
            }
        }

        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /**
     * {@inheritdoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $stream = Utils::streamFor($content);

        return (new Stream())->withStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(string $file, string $mode = 'r'): StreamInterface
    {
        $resource = Utils::tryFopen($file, $mode);
        $stream = Utils::streamFor($resource);

        return (new Stream())->withStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        $stream = Utils::streamFor($resource);

        return (new Stream())->withStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
    }

    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * {@inheritdoc}
     */
    public static function fromGlobalRequest(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = Psr7ServerRequest::getUriFromGlobals();

        return (new ServerRequest($method, $uri))->withRequest(Psr7ServerRequest::fromGlobals());
    }
}
