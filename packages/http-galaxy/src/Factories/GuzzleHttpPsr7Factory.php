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

use BiuradPHP\Http\ServerRequest;
use BiuradPHP\Http\Factory\RequestFactory;
use BiuradPHP\Http\Factory\ResponseFactory;
use BiuradPHP\Http\Factory\ServerRequestFactory;
use BiuradPHP\Http\Factory\StreamFactory;
use BiuradPHP\Http\Factory\UploadedFileFactory;
use BiuradPHP\Http\Factory\UriFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class GuzzleHttpPsr7Factory extends Psr17Factory
{
    /**
     * {@inheritdoc}
     */
    protected function setFactoryCandidates(): void
    {
        $this->responseFactoryClass         = ResponseFactory::class;
        $this->serverRequestFactoryClass    = ServerRequestFactory::class;
        $this->requestFactoryClass          = RequestFactory::class;
        $this->uploadedFilesFactoryClass    = UploadedFileFactory::class;
        $this->streamFactoryClass           = StreamFactory::class;
        $this->uriFactoryClass              = UriFactory::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromGlobalRequest(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) : ServerRequestInterface {
        return ServerRequest::fromGlobals();
    }
}
