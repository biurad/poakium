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

use BiuradPHP\Http\Interfaces\Psr17Interface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use BiuradPHP\Http\Exceptions;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function assert;
use function class_exists;

/**
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class Psr17Factory implements Psr17Interface
{
    /**
     * @var string<ResponseFactoryInterface>
     */
    protected $responseFactoryClass;

    /**
     * @var string<RequestFactoryInterface>
     */
    protected $serverRequestFactoryClass;

    /**
     * @var string<RequestFactoryInterface>
     */
    protected $requestFactoryClass;

    /**
     * @var string<StreamFactoryInterface>
     */
    protected $streamFactoryClass;

    /**
     * @var string<UriFactoryInterface>
     */
    protected $uriFactoryClass;

    /**
     * @var string<UploadedFileFactoryInterface>
     */
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
        $factory = self::isFactoryDecoratorAvailable($this->requestFactoryClass, RequestFactoryInterface::class);
        assert($factory instanceof RequestFactoryInterface);

        return $factory->createRequest($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->responseFactoryClass, ResponseFactoryInterface::class);
        assert($factory instanceof ResponseFactoryInterface);

        return $factory->createResponse($code, $reasonPhrase);
    }

    /**
     * {@inheritdoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);
        assert($factory instanceof StreamFactoryInterface);

        return $factory->createStream($content);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);
        assert($factory instanceof StreamFactoryInterface);

        if ('' === $mode || false === in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
            throw new Exceptions\InvalidPsr17FactoryException('The mode ' . $mode . ' is invalid.');
        }

        return $factory->createStreamFromFile($filename, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, StreamFactoryInterface::class);
        assert($factory instanceof StreamFactoryInterface);

        return $factory->createStreamFromResource($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->uploadedFilesFactoryClass, UploadedFileFactoryInterface::class);
        assert($factory instanceof UploadedFileFactoryInterface);

        return $factory->createUploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->uriFactoryClass, UriFactoryInterface::class);
        assert($factory instanceof UriFactoryInterface);

        return $factory->createUri($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $factory = self::isFactoryDecoratorAvailable($this->streamFactoryClass, ServerRequestFactoryInterface::class);
        assert($factory instanceof ServerRequestFactoryInterface);

        return $factory->createServerRequest($method, $uri, $serverParams);
    }

    /**
     * {@inheritdoc}
     */
    abstract public static function fromGlobalRequest(array $server = null, array $query = null, array $body = null, array $cookies = null, array $files = null) : ServerRequestInterface;

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
        if (!class_exists($httpFactoryClass)) {
            throw new Exceptions\InvalidPsr17FactoryException(sprintf('Psr17 http factory class %s does\'t exists', $httpFactoryClass));
        }

        $reflection = new \ReflectionClass($httpFactoryClass);
        if (! $reflection->implementsInterface($implements)) {
            throw new Exceptions\InvalidPsr17FactoryException(sprintf('%s given does not implement %s', $reflection->getName(), $implements));
        }

        return $reflection->newInstance();
    }
}
