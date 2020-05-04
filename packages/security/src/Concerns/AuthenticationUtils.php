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

namespace BiuradPHP\Security\Concerns;

use LogicException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

/**
 * Extracts Security Errors from Request.
 *
 * @author Boris Vujicic <boris.vujicic@gmail.com>
 */
class AuthenticationUtils
{
    private $requestStack;

    public function __construct(Request $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param bool $clearSession
     * @return AuthenticationException|null
     */
    public function getLastAuthenticationError(bool $clearSession = true)
    {
        $request = $this->getRequest();
        $authenticationException = null;
        $attributes = $request->getAttributes();

        if (isset($attributes[Security::AUTHENTICATION_ERROR])) {
            $authenticationException = $request->getAttribute(Security::AUTHENTICATION_ERROR);
        } elseif (isset($attributes['session']) && ($session = $request->getAttribute('session'))->getSection()->has(Security::AUTHENTICATION_ERROR)) {
            $authenticationException = $session->getSection()->get(Security::AUTHENTICATION_ERROR);

            if ($clearSession) {
                $session->getSection()->delete(Security::AUTHENTICATION_ERROR);
            }
        }

        return $authenticationException;
    }

    /**
     * @return string
     */
    public function getLastUsername()
    {
        $request = $this->getRequest();
        $attributes = $request->getAttributes();

        if (isset($attributes[Security::LAST_USERNAME])) {
            return $request->getAttribute(Security::LAST_USERNAME, '');
        }

        return isset($attributes['session']) ? $request->getAttribute('session')->getSection()->get(Security::LAST_USERNAME, '') : '';
    }

    /**
     * @throws LogicException
     */
    private function getRequest(): Request
    {
        $request = $this->requestStack;

        if (null === $request) {
            throw new LogicException('Request should exist so it can be processed for error.');
        }

        return $request;
    }
}
