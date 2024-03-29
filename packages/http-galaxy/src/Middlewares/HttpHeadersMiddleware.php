<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Handles Default Headers for Request and Response.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class HttpHeadersMiddleware implements MiddlewareInterface
{
    /** @var array<string,array<string,mixed>> */
    private array $config;

    public function __construct(array $headers = [])
    {
        $this->config = $headers += ['response' => [], 'request' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        foreach ($this->config['request'] ?? [] as $reqHeader => $reqValue) {
            $request = $request->withHeader($reqHeader, $reqValue);
        }

        $response = $handler->handle($request);

        foreach ($this->config['response'] ?? [] as $resHeader => $resValue) {
            $response = $response->withHeader($resHeader, $resValue);
        }

        return $response;
    }
}
