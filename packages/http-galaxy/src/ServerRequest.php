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

use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest
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
     * @param null|resource|StreamInterface|string $body         Request body
     * @param string                               $version      Protocol version
     * @param array                                $serverParams Typically the $_SERVER superglobal
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

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return ServerRequestInterface
     */
    public static function fromGlobals()
    {
        $method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers  = getallheaders();
        $uri      = Psr7ServerRequest::getUriFromGlobals();
        $body     = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = \str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'] ?? 'Http/1.1');

        $serverRequest = new static($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(Psr7ServerRequest::normalizeFiles($_FILES));
    }
}
