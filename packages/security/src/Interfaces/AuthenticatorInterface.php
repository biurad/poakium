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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * The interface for all authenticators.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface AuthenticatorInterface
{
    /**
     * Does the authenticator support the given Request?
     *
     * If this returns true, authenticate() will be called. If false, the authenticator will be skipped.
     */
    public function supports(ServerRequestInterface $request): bool;

    /**
     * Create a token for the current request.
     *
     * You may throw any AuthenticationException in this method in case of error (e.g.
     * a UserNotFoundException when the user cannot be found).
     *
     * @param array<string,mixed> $credentials
     *
     * @throws AuthenticationException
     */
    public function authenticate(ServerRequestInterface $request, array $credentials, string $firewallName): ?TokenInterface;
}
