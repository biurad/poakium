<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Factories;

use BiuradPHP\Http\Factory\RequestFactory;
use BiuradPHP\Http\Factory\ResponseFactory;
use BiuradPHP\Http\Factory\ServerRequestFactory;
use BiuradPHP\Http\Factory\StreamFactory;
use BiuradPHP\Http\Factory\UploadedFileFactory;
use BiuradPHP\Http\Factory\UriFactory;
use BiuradPHP\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class GuzzleHttpPsr7Factory extends Psr17Factory
{
    /**
     * {@inheritdoc}
     */
    public static function fromGlobalRequest(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ): ServerRequestInterface {
        return ServerRequest::fromGlobals();
    }

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
}
