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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Prevent content sniffing (MIME sniffing).
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ContentTypeOptionsMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // prevent content sniffing (MIME sniffing)
        if (!$response->hasHeader('X-Content-Type-Options')) {
            return $response->withAddedHeader('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
