<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Interfaces;

interface CookieInterface
{
    /**
     * Creates a new cookie without any attribute validation.
     *
     * @param string $name     the name of the cookie
     * @param string $value    The value of the cookie. This value is stored on the clients
     *                         computer; do not store sensitive information.
     * @param string $path     The path on the server in which the cookie will be available on.
     *                         If set to '/', the cookie will be available within the entire
     *                         domain.
     *                         If set to '/foo/', the cookie will only be available within the
     *                         /foo/
     *                         directory and all sub-directories such as /foo/bar/ of domain. The
     *                         default value is the current directory that the cookie is being set
     *                         in.
     * @param string $domain   The domain that the cookie is available. To make the cookie
     *                         available
     *                         on all subdomains of example.com then you'd set it to
     *                         '.example.com'.
     *                         The . is not required but makes it compatible with more browsers.
     *                         Setting it to www.example.com will make the cookie only available in
     *                         the www subdomain. Refer to tail matching in the spec for details.
     * @param bool   $secure   Indicates that the cookie should only be transmitted over a secure
     *                         HTTPS connection from the client. When set to true, the cookie will
     *                         only be set if a secure connection exists. On the server-side, it's
     *                         on the programmer to send this kind of cookie only on secure
     *                         connection (e.g. with respect to $_SERVER["HTTPS"]).
     * @param bool   $httpOnly When true the cookie will be made accessible only through the HTTP
     *                         protocol. This means that the cookie won't be accessible by
     *                         scripting
     *                         languages, such as JavaScript. This setting can effectively help to
     *                         reduce identity theft through XSS attacks (although it is not
     *                         supported by all browsers).
     * @param string $sameSite Allows running cookies on samesite settings.
     * @param int    $maxAge   Cookie maxAge. This value specified in seconds and declares period
     *                         of time in which cookie will last for.
     *                         value. Expires attribute is HTTP 1.0 only and should be avoided.
     * @param int    $expires  Cookie lifetime. This value specified in seconds and declares period
     *                         of time in which cookie will expire relatively to current time()
     *                         value. Expires attribute is HTTP 1.0 only and should be avoided.
     */
    public static function createWithoutValidation(
        string $name,
        string $value = null,
        ?string $path = '/',
        string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = 'lax',
        $maxAge = null,
        $expires = 0
    ): CookieInterface;

    /**
     * The name of the cookie.
     */
    public function getName(): string;

    /**
     * The value of the cookie. This value is stored on the clients computer; do not store sensitive
     * information.
     */
    public function getValue(): ?string;

    /**
     * Get new cookie with altered value. Original cookie object should not be changed.
     *
     * @param string $value
     */
    public function withValue(string $value): CookieInterface;

    /**
     * The path on the server in which the cookie will be available on.
     *
     * If set to '/', the cookie will be available within the entire domain. If set to '/foo/',
     * the cookie will only be available within the /foo/ directory and all sub-directories such as
     * /foo/bar/ of domain. The default value is the current directory that the cookie is being set
     * in.
     */
    public function getPath(): ?string;

    /**
     * The domain that the cookie is available. To make the cookie available on all subdomains of
     * example.com then you'd set it to '.example.com'. The . is not required but makes it
     * compatible with more browsers. Setting it to www.example.com will make the cookie only
     * available in the www subdomain. Refer to tail matching in the spec for details.
     */
    public function getDomain(): ?string;

    /**
     * Indicates that the cookie should only be transmitted over a secure HTTPS connection from the
     * client. When set to true, the cookie will only be set if a secure connection exists.
     * On the server-side, it's on the programmer to send this kind of cookie only on secure
     * connection
     * (e.g. with respect to $_SERVER["HTTPS"]).
     */
    public function isSecure(): bool;

    /**
     * When true the cookie will be made accessible only through the HTTP protocol. This means that
     * the cookie won't be accessible by scripting languages, such as JavaScript. This setting can
     * effectively help to reduce identity theft through XSS attacks (although it is not supported
     * by all browsers).
     */
    public function isHttpOnly(): bool;

    /**
     * The time the cookie expires. This is a Unix timestamp so is in number of seconds since the
     * epoch. In other words, you'll most likely set this with the time function plus the number of
     * seconds before you want it to expire. Or you might use mktime.
     *
     * Will return null if lifetime is not specified.
     *
     * @return int|null
     */
    public function getExpires(): ?int;

    /**
     * Gets the max-age attribute, always returns an integer. The reason is if maxAge is not set
     * in cookie instance, an integer value is returned from `getExpires()` method.
     *
     * Returns `$this->getExpires() - time();` if $maxAge is null and expires is greater than 0.
     */
    public function getMaxAge(): int;

    /**
     * Gets the SameSite attribute, fully supported in php 7.3 above.
     *
     * @return string|null
     */
    public function getSameSite(): ?string;

    /**
     * Checks whether this cookie is meant for this path.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.4
     *
     * @param string $path
     */
    public function matchPath($path): bool;

    /**
     * Checks whether this cookie is meant for this domain.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.3
     *
     * @param string $domain
     */
    public function matchDomain($domain): bool;

    /**
     * Convert cookie instance to string.
     *
     * @see http://www.w3.org/Protocols/rfc2109/rfc2109
     *
     * @return string
     */
    public function __toString(): string;
}
