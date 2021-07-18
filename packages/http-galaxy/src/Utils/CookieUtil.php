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

namespace Biurad\Http\Utils;

final class CookieUtil
{
    /**
     * Proxy for the native setcookie function - to allow mocking in unit tests so that they do not fail when headers
     * have been sent. But can serve better when used natively to set cookies.
     *
     * @param string      $name
     * @param string      $value
     * @param int         $expire
     * @param string      $path
     * @param string      $domain
     * @param bool        $secure
     * @param bool        $httponly
     * @param string|null $sameSite
     *
     * @return bool
     *
     * @see setcookie
     */
    public static function setcookie(
        $name,
        $value = '',
        $expires = 0,
        $path = '',
        $domain = '',
        $secure = false,
        $httponly = false,
        $samesite = null
    ) {
        if (\PHP_VERSION_ID >= 70300) {
            return \setcookie($name, $value, \compact('path', 'expires', 'domain', 'secure', 'httponly', 'samesite'));
        }

        return \setcookie(
            $name,
            $value,
            $expires,
            $path . ($samesite ? "; SameSite=$samesite" : ''),
            $domain,
            $secure,
            $httponly
        );
    }

    public static function normalizeMaxAge(int $maxAge): \DateInterval
    {
        return new \DateInterval('PT' . $maxAge . 'S');
    }

    /**
     * @param \DateTimeInterface|int|string $expires
     *
     * @throws \InvalidArgumentException if invalid value is provided
     */
    public static function normalizeExpires($expires): \DateTime
    {
        // convert expiration time to a Unix timestamp
        if ($expires instanceof \DateTimeInterface) {
            return $expires;
        }

        if (!\is_numeric($expires)) {
            $expires = \strtotime($expires);
        }

        if (!\is_int($expires)) {
            throw new \InvalidArgumentException(\sprintf('Invalid expires "%s" provided', $expires));
        }

        return new \DateTime('@' . $expires);
    }
}
