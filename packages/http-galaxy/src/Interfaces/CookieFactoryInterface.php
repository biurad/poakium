<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Cookie;

interface CookieFactoryInterface
{
    /**
     * Checks if there is a cookie.
     */
    public function hasCookie(string $CookieName): bool;

    /**
     * Queue a cookie to send with the next response.
     */
    public function addCookie(Cookie ...$cookies): void;

    /**
     * Removes a cookie from queue.
     */
    public function removeCookie(string $CookieName): void;

    /**
     * Finds and returns the cookie based on the name.
     *
     * @param string $name cookie name to search for
     *
     * @return Cookie|null cookie that was found or null if not found
     */
    public function getCookieByName(string $name): ?Cookie;

    /**
     * Checks if there are cookies.
     */
    public function hasCookies(): bool;

    /**
     * Returns the cookies which have been queued for the next request.
     *
     * @return Cookie[]
     */
    public function getCookies(): array;

    /**
     * Finds matching cookies based on a callable.
     *
     * @param callable(Cookie) $match A callable to match cookies
     *
     * @return Cookie[]
     */
    public function getMatchingCookies(callable $match): array;

    /**
     * Populates the cookie factory from a HttP response Set-Cookie header.
     *
     * @param ResponseInterface $response The response object to populate from.
     */
    public function fromResponse(ResponseInterface $response): self;

    /**
     * Removes all cookies.
     */
    public function clear(): void;
}
