<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security\Event;

use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched after an error during authentication.
 *
 * Listeners to this event can change state based on authentication
 * failure (e.g. to implement login throttling).
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AuthenticationFailureEvent extends Event
{
    private AuthenticationException $exception;
    private AuthenticatorInterface $authenticator;
    private ServerRequestInterface $request;
    private ?ResponseInterface $response;

    public function __construct(AuthenticationException $exception, AuthenticatorInterface $authenticator, ServerRequestInterface $request, ?ResponseInterface $response)
    {
        $this->exception = $exception;
        $this->authenticator = $authenticator;
        $this->request = $request;
        $this->response = $response;
    }

    public function getException(): AuthenticationException
    {
        return $this->exception;
    }

    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
