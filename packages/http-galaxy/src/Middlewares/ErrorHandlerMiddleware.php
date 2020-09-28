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

use Biurad\Http\Exceptions\ClientException;
use Biurad\Http\Exceptions\ClientExceptions\AccessDeniedException;
use Biurad\Http\Exceptions\ClientExceptions\BadRequestException;
use Biurad\Http\Exceptions\ClientExceptions\ConflictException;
use Biurad\Http\Exceptions\ClientExceptions\GoneException;
use Biurad\Http\Exceptions\ClientExceptions\LengthRequiredException;
use Biurad\Http\Exceptions\ClientExceptions\MethodNotAllowedException;
use Biurad\Http\Exceptions\ClientExceptions\NotAcceptableException;
use Biurad\Http\Exceptions\ClientExceptions\NotFoundException;
use Biurad\Http\Exceptions\ClientExceptions\PreconditionFailedException;
use Biurad\Http\Exceptions\ClientExceptions\PreconditionRequiredException;
use Biurad\Http\Exceptions\ClientExceptions\ServerErrorException;
use Biurad\Http\Exceptions\ClientExceptions\ServiceUnavailableException;
use Biurad\Http\Exceptions\ClientExceptions\TooManyRequestsException;
use Biurad\Http\Exceptions\ClientExceptions\UnauthorizedException;
use Biurad\Http\Exceptions\ClientExceptions\UnprocessableEntityException;
use Biurad\Http\Exceptions\ClientExceptions\UnsupportedMediaTypeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Default response errorhandler middleware.
 *
 * Wraps Client and Routing exceptions into proper response.
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Request        $request
     * @param RequestHandler $handler
     *
     * @return ResponseInterface
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

        // Handle Headers Error
        if ($response->getStatusCode() >= 400) {
            for ($i = 400; $i < 600; $i++) {
                switch ($i) {
                    case 400:
                        $exception = new BadRequestException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 401:
                        $exception = new UnauthorizedException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 403:
                        $exception = new AccessDeniedException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 404:
                        throw new NotFoundException();

                    case 405:
                        throw new MethodNotAllowedException();

                    case 406:
                        $exception = new NotAcceptableException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 409:
                        $exception = new ConflictException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 410:
                        $exception = new GoneException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 411:
                        $exception = new LengthRequiredException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 412:
                        $exception = new PreconditionFailedException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 415:
                        $exception = new UnsupportedMediaTypeException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 422:
                        $exception = new UnprocessableEntityException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 428:
                        $exception = new PreconditionRequiredException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 429:
                        $exception = new TooManyRequestsException();
                        $exception->withResponse($response);

                        throw $exception;

                    case 500:
                        throw new ServerErrorException();

                    case 503:
                        $exception = new ServiceUnavailableException();
                        $exception->withResponse($response);

                        throw $exception;

                    default:
                        throw new ClientException($i);
                }
            }
        }

        // remove headers that MUST NOT be included with 304 Not Modified responses
        return $response;
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
