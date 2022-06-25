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

use Biurad\Http\Request;
use Biurad\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Fixes the Response headers based on the Request.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class PrepareResponseMiddleware implements MiddlewareInterface
{
    private string $charset;

    private bool $addContentLanguageHeader;

    public function __construct(string $charset = 'UTF-8', bool $addContentLanguageHeader = false)
    {
        $this->charset = $charset;
        $this->addContentLanguageHeader = $addContentLanguageHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response instanceof Response && $request instanceof Request) {
            $httpResponse = $response->getResponse();

            if (null === $httpResponse->getCharset()) {
                $httpResponse->setCharset($this->charset);
            }

            if ($this->addContentLanguageHeader && !$httpResponse->isInformational() && !$httpResponse->isEmpty() && !$response->hasHeader('Content-Language')) {
                $httpResponse->headers->set('Content-Language', $request->getRequest()->getLocale());
            }

            if (null !== $request->getAttribute('_vary_by_language')) {
                $httpResponse->setVary('Accept-Language', false);
            }

            $response = $response->withResponse($response->getResponse()->prepare($request->getRequest()));
        }

        return $response;
    }
}
