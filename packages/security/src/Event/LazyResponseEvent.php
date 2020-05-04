<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use BiuradPHP\Security\Exceptions\LazyResponseException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Wraps a lazily computed response in a signaling exception.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class LazyResponseEvent extends LoginEvent
{
    private $event;

    public function __construct(parent $event)
    {
        $this->event = $event;
    }

    /**
     * Returns the response object.
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->event->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse(?ResponseInterface $response)
    {
        $this->stopPropagation();
        $this->event->stopPropagation();

        throw new LazyResponseException($response);
    }

    /**
     * Returns whether a response was set.
     *
     * @return bool Whether a response was set
     */
    public function hasResponse()
    {
        return $this->event->hasResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest(): Request
    {
        return $this->event->getRequest();
    }

    public function hasAuthenticationToken()
    {
        return $this->event->hasAuthenticationToken();
    }

    public function setAuthenticationToken(?TokenInterface $authenticationToken)
    {
        $this->event->setAuthenticationToken($authenticationToken);
    }

    public function getAuthenticationToken(): TokenInterface
    {
        return $this->event->getAuthenticationToken();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestType(): int
    {
        return $this->event->getRequestType();
    }
}
