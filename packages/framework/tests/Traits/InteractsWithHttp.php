<?php declare(strict_types=1);

/*
 * This file is part of RadePHP Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Traits;

use Biurad\Http\ServerRequest;
use Flight\Routing\Route;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

trait InteractsWithHttp
{
    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a matched route.
     */
    public function matchRoute(
        string|UriInterface $uri,
        string $method = 'GET',
        array $attributes = [],
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ?Route {
        $request = $this->request($uri, $method, $query, $headers, $cookies);

        foreach ($attributes as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $this->makeApp()->getRouter()->matchRequest($request);
    }

    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a server request.
     *
     * @param resource|StreamInterface|string|null $body
     */
    public function request(
        string|UriInterface $uri,
        string $method = 'GET',
        $body = 'php://input',
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ServerRequest {
        $servers = $_SERVER + ['SERVER_PROTOCOL' => 'HTTP/1.1'];

        return (new ServerRequest($method, $uri, $headers, $body, '1.1', $servers))->withCookieParams($cookies)->withQueryParams($query);
    }
}
