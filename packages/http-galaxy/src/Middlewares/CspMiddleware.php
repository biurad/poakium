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

namespace BiuradPHP\Http\Middlewares;

use BiuradPHP\Http\Csp\ContentSecurityPolicy;
use BiuradPHP\Http\Csp\NonceGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use BiuradPHP\Http\Interfaces\CspInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Handles Content-Security-Policy HTTP Middleware.
 *
 * Provides tools for working with the Content-Security-Policy header
 * to help defeat XSS attacks.
 *
 * @see     http://www.w3.org/TR/CSP/
 * @see     http://www.html5rocks.com/en/tutorials/security/content-security-policy/
 * @see     http://content-security-policy.com/
 * @see     https://www.owasp.org/index.php/Content_Security_Policy
 *
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
class CspMiddleware implements MiddlewareInterface
{
    /** @var ContentSecurityPolicy */
    private $csp;

    /**
     * @param CspInterface $csp
     */
    public function __construct(?CspInterface $csp)
    {
        $this->csp = $csp ?? new ContentSecurityPolicy(new NonceGenerator());
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return ResponseInterface
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = clone $handler->handle($request);
        $nonce = $this->csp->updateResponseHeaders($request, $response);

        // Incase it's disabled
        if ($nonce instanceof ResponseInterface) {
            return $nonce;
        }

        return $response;
    }
}
