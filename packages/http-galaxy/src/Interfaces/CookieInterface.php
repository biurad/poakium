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

interface CookieInterface
{
    /**
     * Convert cookie instance to string.
     *
     * @see http://www.w3.org/Protocols/rfc2109/rfc2109
     *
     * @return string
     */
    public function __toString();

    /**
     * Create a new SetCookie object from a string.
     *
     * @param string $cookie Set-Cookie header string
     */
    public static function fromString(string $cookie): self;

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set the cookie name.
     *
     * @param string $name Cookie name
     */
    public function setName($name): void;

    /**
     * The value of the cookie. This value is stored on the clients computer;
     * do not store sensitive information.
     *
     * @return string|null
     */
    public function getValue();

    /**
     * Set new cookie with altered value.
     *
     * @param string $value Cookie value
     */
    public function setValue($value): void;

    /**
     * The path on the server in which the cookie will be available on.
     *
     * If set to '/', the cookie will be available within the entire domain. If set to '/foo/',
     * the cookie will only be available within the /foo/ directory and all sub-directories such as
     * /foo/bar/ of domain. The default value is the current directory that the cookie is being set
     * in.
     *
     * @return string
     */
    public function getPath();

    /**
     * Set new cookie with altered path.
     *
     * @param string $path Path of the cookie
     */
    public function setPath($path): void;

    /**
     * The domain that the cookie is available. To make the cookie available on all subdomains of
     * example.com then you'd set it to '.example.com'. The . is not required but makes it
     * compatible with more browsers. Setting it to www.example.com will make the cookie only
     * available in the www subdomain. Refer to tail matching in the spec for details.
     *
     * @return string|null
     */
    public function getDomain();

    /**
     * Set new cookie with altered domain.
     *
     * @param string $domain
     */
    public function setDomain($domain): void;

    /**
     * Set new cookie with altered secure.
     *
     * @param bool $secure Set to true or false if secure
     */
    public function setSecure($secure): void;

    /**
     * Set new cookie with altered httpOnly.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly($httpOnly): void;

    /**
     * Set whether or not this is a session cookie.
     *
     * @param bool $discard Set to true or false if this is a session cookie
     */
    public function setDiscard($discard): void;

    /**
     * Indicates that the cookie should only be transmitted over a secure HTTPS connection from the
     * client. When set to true, the cookie will only be set if a secure connection exists.
     * On the server-side, it's on the programmer to send this kind of cookie only on secure
     * connection
     * (e.g. with respect to $_SERVER["HTTPS"]).
     *
     * @return bool|null
     */
    public function getSecure();

    /**
     * When true the cookie will be made accessible only through the HTTP protocol. This means that
     * the cookie won't be accessible by scripting languages, such as JavaScript. This setting can
     * effectively help to reduce identity theft through XSS attacks (although it is not supported
     * by all browsers).
     *
     * @return bool
     */
    public function getHttpOnly();

    /**
     * Get whether or not this is a session cookie.
     *
     * @return bool|null
     */
    public function getDiscard();

    /**
     * The time the cookie expires. This is a Unix timestamp so is in number of seconds since the
     * epoch. In other words, you'll most likely set this with the time function plus the number of
     * seconds before you want it to expire. Or you might use mktime.
     *
     * Will return null if lifetime is not specified.
     *
     * @return string|int|null
     */
    public function getExpires();

    /**
     * Set new cookie with altered expires.
     *
     * @param null|\DateTimeInterface|int|string $expires
     */
    public function setExpires($expires): void;

    /**
     * Gets the maximum lifetime of the cookie in seconds.
     *
     * @return int|null
     */
    public function getMaxAge(): ?int;

    /**
     * Set new cookie with altered maxAge.
     *
     * @param int $maxAge
     */
    public function setMaxAge($maxAge): void;

    /**
     * Gets the SameSite attribute, fully supported in php 7.3 above.
     *
     * @return null|string
     */
    public function getSameSite(): ?string;

    /**
     * Set new cookie with altered sameSite.
     *
     * @param null|string $sameSite
     */
    public function setSameSite($sameSite): void;

    /**
     * Check if the cookie is expired.
     */
    public function isExpired(): bool;

    /**
     * Check if the cookie is valid according to RFC 6265.
     *
     * @return bool|string Returns true if valid or an error message if invalid
     */
    public function validate();

    /**
     * Checks if this cookie represents the same cookie as $cookie.
     *
     * This does not compare the values, only name, domain and path.
     *
     * @param CookieInterface $cookie
     *
     * @return bool
     */
    public function matches(self $cookie): bool;

    /**
     * Check if the cookie matches a path value.
     *
     * A request-path path-matches a given cookie-path if at least one of
     * the following conditions holds:
     *
     * - The cookie-path and the request-path are identical.
     * - The cookie-path is a prefix of the request-path, and the last
     *   character of the cookie-path is %x2F ("/").
     * - The cookie-path is a prefix of the request-path, and the first
     *   character of the request-path that is not included in the cookie-
     *   path is a %x2F ("/") character.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.4
     *
     * @param string $requestPath Path to check against
     *
     * @return bool
     */
    public function matchesPath(string $requestPath): bool;

    /**
     * Check if the cookie matches a domain value.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.3
     *
     * @param string $domain Domain to check against
     *
     * @return bool
     */
    public function matchesDomain($domain): bool;
}
