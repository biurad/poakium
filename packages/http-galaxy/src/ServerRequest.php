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

namespace Biurad\Http;

use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest.
 */
class ServerRequest implements ServerRequestInterface
{
    use Traits\ServerRequestDecoratorTrait {
        getRequest as private;
    }

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param resource|StreamInterface|string|null $body         Request body
     * @param string                               $version      Protocol version
     * @param array<string,mixed>                  $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->message = new Psr7ServerRequest($method, $uri, $headers, $body, $version, $serverParams);
    }
}
