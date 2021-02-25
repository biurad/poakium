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

namespace Biurad\Http\Factories;

use Biurad\Http\Exceptions;
use Biurad\Http\Interfaces\Psr17Interface;
use Biurad\Http\Request;
use Biurad\Http\Response;
use Biurad\Http\ServerRequest;
use Biurad\Http\Stream;
use Biurad\Http\UploadedFile;
use Biurad\Http\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class Psr17Factory implements Psr17Interface
{
    /** @var string<ResponseFactoryInterface> */
    protected $responseFactoryClass;

    /** @var string<RequestFactoryInterface> */
    protected $serverRequestFactoryClass;

    /** @var string<RequestFactoryInterface> */
    protected $requestFactoryClass;

    /** @var string<StreamFactoryInterface> */
    protected $streamFactoryClass;

    /** @var string<UriFactoryInterface> */
    protected $uriFactoryClass;

    /** @var string<UploadedFileFactoryInterface> */
    protected $uploadedFilesFactoryClass;

    public function __construct()
    {
        return $this->setFactoryCandidates();
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        /** @var RequestFactoryInterface $factory */
        $factory  = self::isFactoryDecoratorAvailable($this->requestFactoryClass, RequestFactoryInterface::class);
        $response = $factory->createRequest($method, $uri);

        if (!$response instanceof Request) {
            $request  = new Request($response->getMethod(), $response->getUri());
            $response = $request->withRequest($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        /** @var ResponseFactoryInterface $factory */
        $factory = self::isFactoryDecoratorAvailable($this->responseFactoryClass, ResponseFactoryInterface::class);

        $response = $factory->createResponse($code, $reasonPhrase);

        if (!$response instanceof Response) {
            $response = (new Response())->withResponse($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        /** @var StreamFactoryInterface $factory */
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);

        $response = $factory->createStream($content);

        if (!$response instanceof Stream) {
            $response = (new Stream())->withStream($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        /** @var StreamFactoryInterface $factory */
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);

        if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
            throw new Exceptions\InvalidPsr17FactoryException('The mode ' . $mode . ' is invalid.');
        }
        $response = $factory->createStreamFromFile($filename, $mode);

        if (!$response instanceof Stream) {
            $response = (new Stream())->withStream($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        /** @var StreamFactoryInterface $factory */
        $factory  = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);
        $response = $factory->createStreamFromResource($resource);

        if (!$response instanceof Stream) {
            $response = (new Stream())->withStream($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        /** @var UploadedFileFactoryInterface $factory */
        $factory = self::isFactoryDecoratorAvailable(
            $this->uploadedFilesFactoryClass,
            UploadedFileFactoryInterface::class
        );

        $response = $factory->createUploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);

        if (!$response instanceof UploadedFile) {
            $uploaded = new UploadedFile($response->getStream(), $response->getSize(), $response->getError());
            $response = $uploaded->withUploadFile($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        /** @var UriFactoryInterface $factory */
        $factory  = self::isFactoryDecoratorAvailable($this->uriFactoryClass, UriFactoryInterface::class);
        $response = $factory->createUri($uri);

        if (!$response instanceof Uri) {
            $response = (new Uri())->withUri($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        /** @var ServerRequestFactoryInterface $factory */
        $factory  = self::isFactoryDecoratorAvailable($this->serverRequestFactoryClass, ServerRequestFactoryInterface::class);
        $response = $factory->createServerRequest($method, $uri, $serverParams);

        if (!$response instanceof ServerRequest) {
            $request  = new ServerRequest($response->getMethod(), $response->getUri());
            $response = $request->withRequest($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    abstract public static function fromGlobalRequest(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ): ServerRequestInterface;

    /**
     * Register your Psr17 Http Factory Implemetation here.
     */
    abstract protected function setFactoryCandidates(): void;

    /**
     * @final
     *
     * @return object
     */
    protected static function isFactoryDecoratorAvailable(string $httpFactoryClass, string $implements)
    {
        if (!\class_exists($httpFactoryClass)) {
            throw new Exceptions\InvalidPsr17FactoryException(
                \sprintf('Psr17 http factory class %s does\'t exists', $httpFactoryClass)
            );
        }

        $reflection = new \ReflectionClass($httpFactoryClass);

        if (!$reflection->implementsInterface($implements)) {
            throw new Exceptions\InvalidPsr17FactoryException(
                \sprintf('%s given does not implement %s', $reflection->getName(), $implements)
            );
        }

        return $reflection->newInstance();
    }
}
