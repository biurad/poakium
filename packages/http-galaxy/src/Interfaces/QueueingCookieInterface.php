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

interface QueueingCookieInterface
{
    /**
     * Checks if there is a cookie.
     *
     * @param CookieInterface $cookie
     */
    public function hasCookie(CookieInterface $cookie): bool;

    /**
     * Queue a cookie to send with the next response.
     *
     * @param array|CookieInterface $parameters
     */
    public function addCookie(...$parameters): void;

    /**
     * Removes a cookie from queue.
     *
     * @param CookieInterface $cookie
     */
    public function removeCookie(CookieInterface $cookie): void;

    /**
     * Finds and returns the cookie based on the name
     *
     * @param string $name cookie name to search for
     *
     * @return null|CookieInterface cookie that was found or null if not found
     */
    public function getCookieByName($name): ?CookieInterface;

    /**
     * Checks if there are cookies.
     */
    public function hasCookies(): bool;

    /**
     * Returns the cookies which have been queued for the next request.
     *
     * @return array|CookieInterface[]
     */
    public function getCookies(): array;

    /**
     * Determine if a cookie has been queued.
     *
     * @param string $CookieName The cookie's name
     */
    public function hasQueuedCookie($CookieName): bool;

    /**
     * Remove a cookie from the queue.
     *
     * @param string $CookieName The cookie's name
     */
    public function unqueueCookie($CookieName): void;

    /**
     * Returns all matching cookies.
     *
     * @param CookieInterface $cookie
     *
     * @return CookieInterface[]
     */
    public function getMatchingCookies(CookieInterface $cookie): array;

    /**
     * Removes all cookies.
     */
    public function clear(): void;
}
