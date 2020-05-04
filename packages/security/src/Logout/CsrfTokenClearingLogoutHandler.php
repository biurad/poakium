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
use BiuradPHP\Security\Interfaces\LogoutHandlerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

/**
 * @author Christian Flothmann <christian.flothmann@sensiolabs.de>
 */
class CsrfTokenClearingLogoutHandler implements LogoutHandlerInterface
{
    private $csrfTokenStorage;

    public function __construct(ClearableTokenStorageInterface $csrfTokenStorage)
    {
        $this->csrfTokenStorage = $csrfTokenStorage;
    }

    public function logout(Request $request, ResponseInterface $response, TokenInterface $token)
    {
        $this->csrfTokenStorage->clear();
    }
}
