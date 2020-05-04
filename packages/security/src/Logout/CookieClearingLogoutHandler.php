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

namespace BiuradPHP\Security\Logout;

use Psr\Http\Message\ResponseInterface;
use BiuradPHP\Http\Interfaces\QueueingCookieInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use BiuradPHP\Security\Interfaces\LogoutHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This handler clears the passed cookies when a user logs out.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CookieClearingLogoutHandler implements LogoutHandlerInterface
{
    private $cookies;

    /**
     * @param array $cookies An array of cookie names to unset
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * Implementation for the LogoutHandlerInterface. Deletes all requested cookies.
     *
     * @param Request $request
     * @param ResponseInterface $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, ResponseInterface $response, TokenInterface $token)
    {
        $cookie = $request->getAttribute('cookie');
        assert($cookie instanceof QueueingCookieInterface);

        foreach ($this->cookies as $cookieName => $cookieData) {
            $cookie->unqueueCookie($cookieName);
        }
    }
}
