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

namespace Biurad\Security\Handler;

use Biurad\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

/**
 * The default logout handler.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LogoutHandler
{
    private TokenStorageInterface $tokenStorage;
    private ?ClearableTokenStorageInterface $csrfTokenStorage;
    private ?SessionInterface $session;
    private ?RememberMeHandler $rememberMeHandler;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ClearableTokenStorageInterface $csrfTokenStorage = null,
        RememberMeHandler $rememberMeHandler = null,
        SessionInterface $session = null
    )
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenStorage = $csrfTokenStorage;
        $this->rememberMeHandler = $rememberMeHandler;
    }

    /**
     * Handler for:
     * - clearing invalidating the current session
     * - clearing the token storage
     * - clearing the CSRF token storage
     * - clearing the remember me cookie if needed.
     *
     * @return array<int,Cookie> The remember me clearing cookies if any.
     */
    public function handle(ServerRequestInterface $request): array
    {
        $this->tokenStorage->setToken();

        if (null !== $this->csrfTokenStorage) {
            $this->csrfTokenStorage->clear();
        }

        if (null === $this->session && $request instanceof Request && $request->getRequest()->hasSession()) {
            $this->session = $request->getRequest()->getSession();
        }

        if (null !== $this->session) {
            $this->session->invalidate();
        }

        if (null !== $this->rememberMeHandler) {
            return $this->rememberMeHandler->clearRememberMeCookies($request);
        }

        return null !== $this->rememberMeHandler ? $this->rememberMeHandler->clearRememberMeCookies($request) : [];
    }
}
