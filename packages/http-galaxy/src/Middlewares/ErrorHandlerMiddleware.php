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

namespace Biurad\Http\Middlewares;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Throw exception when the response of a request is not acceptable.
 *
 * Status codes 400-499 lead to a ClientErrorException, status 500-599 to a ServerErrorException.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Incase response is empty
        if ($this->isResponseEmpty($response)) {
            // prevent PHP from sending the Content-Type header based on default_mimetype
            \ini_set('default_mimetype', '');

            $response = $response
                ->withoutHeader('Allow')
                ->withoutHeader('Content-MD5')
                ->withoutHeader('Content-Type')
                ->withoutHeader('Content-Length');
        }

        // If is below 4XX Responses code will never throw an exception
        if ($response->getStatusCode() < 400) {
            return $response;
        }

        throw RequestException::create($request, $response);
    }

    /**
     * Asserts response body is empty or status code is 204, 205 or 304
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function isResponseEmpty(ResponseInterface $response): bool
    {
        $contents = (string) $response->getBody();

        return empty($contents) ||
            ($response->getStatusCode() >= 100 && $response->getStatusCode() < 200) ||
            (\in_array($response->getStatusCode(), [204, 304]));
    }
}
