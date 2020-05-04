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
use Symfony\Contracts\EventDispatcher\Event;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginEvent extends Event
{
    private $response;
    private $request;
    private $requestType;
    private $authenticationToken;

    /**
     * @param Request $request , The Request to listen to.
     * @param int|null $requestType
     */
    public function __construct(Request $request, ?int $requestType = null)
    {
        $this->request      = $request;
        $this->requestType  = $requestType;
    }

    /**
     * Returns the response object.
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }


    /**
     * Sets a response and stops event propagation.
     * 
     * @param ResponseInterface|null $response
     */
    public function setResponse(?ResponseInterface $response)
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    /**
     * Sets a request to replace currently processing request
     * 
     * @param Request|null $request
     */
    public function setRequest(?Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns whether a response was set.
     *
     * @return bool Whether a response was set
     */
    public function hasResponse()
    {
        return null !== $this->response;
    }

    /**
     * Returns the request the kernel is currently processing.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function hasAuthenticationToken()
    {
        return null !== $this->authenticationToken;
    }

    public function setAuthenticationToken(?TokenInterface $authenticationToken)
    {
        $this->authenticationToken = $authenticationToken;
    }

    public function getAuthenticationToken(): TokenInterface
    {
        return $this->authenticationToken;
    }

    /**
     * Returns the request type the kernel is currently processing.
     *
     * @return int One of KernelInterface::MASTER_REQUEST and
     *             KernelInterface::SUB_REQUEST
     */
    public function getRequestType()
    {
        return $this->requestType;
    }
}
