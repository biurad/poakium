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

namespace Biurad\Security\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface AuthenticatorInterface
{
    /**
     * Does the authenticator support the given Request?
     *
     * If this returns true, authenticate() will be called. If false, the authenticator will be skipped.
     *
     * Returning null means authenticate() can be called lazily when accessing the token storage.
     */
    public function supports(ServerRequestInterface $request): bool;

    /**
     * Create a passport for the current request.
     *
     * The passport contains the user, token and any additional information
     * that has to be checked by the Symfony Security system. For example, a login
     * form authenticator will probably return a passport containing the user, the
     * presented password and the CSRF token value.
     *
     * You may throw any AuthenticationException in this method in case of error (e.g.
     * a UserNotFoundException when the user cannot be found).
     *
     * @param array<string,mixed> $credentials
     *
     * @throws AuthenticationException
     */
    public function authenticate(ServerRequestInterface $request, array $credentials): ?TokenInterface;

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface;
}
