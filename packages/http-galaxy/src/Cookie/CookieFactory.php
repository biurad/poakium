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

namespace BiuradPHP\Http\Cookie;

use BiuradPHP\Http\Interfaces\CookieInterface;

/**
 * Represent singular cookie header value with packing abilities.
 *
 * @see http://tools.ietf.org/search/rfc6265
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class CookieFactory implements CookieInterface
{
    const SAMESITE_COLLECTION = ['lax', 'strict', 'none', null];

    /**
     * The name of the cookie.
     *
     * @var string
     */
    private $name = '';

    /**
     * The value of the cookie. This value is stored on the clients computer; do not store sensitive
     * information.
     *
     * @var string|null
     */
    private $value = null;

    /**
     * Cookie lifetime. This value specified in seconds and declares period of time in which cookie
     * will expire relatively to current time() value.
     *
     * NOTE: Expires attribute is HTTP 1.0 only and should be avoided.
     *
     * @var int|null
     */
    private $expires = null;

    /**
     * Cookie maxAge. This value specified in seconds and declares period of time in which cookie
     * will last for.
     *
     * @var int|null
     */
    private $maxAge = null;

    /**
     * The path on the server in which the cookie will be available on.
     *
     * If set to '/', the cookie will be available within the entire domain. If set to '/foo/',
     * the cookie will only be available within the /foo/ directory and all sub-directories such as
     * /foo/bar/ of domain. The default value is the current directory that the cookie is being set
     * in.
     *
     * @var string|null
     */
    private $path = null;

    /**
     * The domain that the cookie is available. To make the cookie available on all subdomains of
     * example.com then you'd set it to '.example.com'. The . is not required but makes it
     * compatible with more browsers. Setting it to www.example.com will make the cookie only
     * available in the www subdomain. Refer to tail matching in the spec for details.
     *
     * @var string|null
     */
    private $domain = null;

    /**
     * Indicates that the cookie should only be transmitted over a secure HTTPS connection from the
     * client. When set to true, the cookie will only be set if a secure connection exists.
     * On the server-side, it's on the programmer to send this kind of cookie only on secure
     * connection
     * (e.g. with respect to $_SERVER["HTTPS"]).
     *
     * @var bool|null
     */
    private $secure = null;

    /**
     * When true the cookie will be made accessible only through the HTTP protocol. This means that
     * the cookie won't be accessible by scripting languages, such as JavaScript. This setting can
     * effectively help to reduce identity theft through XSS attacks (although it is not supported
     * by all browsers).
     *
     * @var bool
     */
    private $httpOnly = true;

    /**
     * Indicates the SameSite Attribute on session
     *
     * @var string
     */
    private $sameSite = 'lax';

    /**
     * New Cookie instance, cookies used to schedule cookie set while dispatching Response.
     *
     * @see http://php.net/manual/en/function.setcookie.php
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
     *
     * @throws \InvalidArgumentException If name, value or max age is not valid.
     */
    public function __construct(
        ?string $name = null,
        string $value = null,
        ?string $path = '/',
        string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = 'lax',
        $maxAge = null,
        $expires = 0
    ) {
        // Validates cookie attributes.
        CookieUtil::validateName($name);
        CookieUtil::validateValue($value);
        CookieUtil::validateMaxAge($maxAge);

        // convert expiration time to a Unix timestamp
        if ($expires instanceof \DateTimeInterface) {
            $expires = $expires->format('U');
        } elseif (!is_numeric($expires)) {
            $expires = strtotime($expires);

            if (false === $expires) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        if (false !== $secure) {
            $httpOnly = false;
        }

        $this->name = $name;
        $this->value = $value;
        $this->maxAge = $maxAge;
        $this->expires = $expires;
        $this->path = CookieUtil::normalizePath($path);
        $this->domain = CookieUtil::normalizeDomain($domain);
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;

        if (empty($sameSite)) {
            $sameSite = null;
        } elseif (null !== $sameSite) {
            $sameSite = strtolower($sameSite);
        }

        if (!\in_array($sameSite, self::SAMESITE_COLLECTION, true)) {
            throw new \InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;
    }

    /**
     * {@inheritdoc}
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
    ): CookieInterface {
        $cookie =  new self('name', null, $path, $domain, $secure, $httpOnly, $sameSite, null, $expires);
        $cookie->name = $name;
        $cookie->value = $value;
        $cookie->maxAge = $maxAge;

        return $cookie;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @return CookieFactory
     */
    public function withValue(string $value): CookieInterface
    {
        CookieUtil::validateValue($value);

        $cookie = clone $this;
        $cookie->value = $value;

        return $cookie;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * The domain that the cookie is available. To make the cookie available on all subdomains of
     * example.com then you'd set it to '.example.com'. The . is not required but makes it
     * compatible with more browsers. Setting it to www.example.com will make the cookie only
     * available in the www subdomain. Refer to tail matching in the spec for details.
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpires(): ?int
    {
        return $this->expires;
    }

    /**
     * Check whether the cookie has expired
     *
     * Always returns false if the cookie is a session cookie (has no expiry time)
     *
     * @param int|null $now Timestamp to consider as "now"
     * @return boolean
     */
    public function isExpired(int $now = null)
    {
        $now = null === $now ? time() : $now;

        if (isset($this->expires) && $this->expires < $now) {
            return true;
        }

        return false;
    }

    /**
     * Gets the max-age attribute.
     *
     * @return int
     */
    public function getMaxAge(): int
    {
        return ($this->expires > 0 && null === $this->maxAge) ? $this->expires - time() : $this->maxAge;
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * Checks if this cookie represents the same cookie as $cookie.
     *
     * This does not compare the values, only name, domain and path.
     *
     * @param CookieInterface $cookie
     */
    public function match(self $cookie): bool
    {
        return $this->name === $cookie->name && $this->domain === $cookie->domain and $this->path === $cookie->path;
    }

    /**
     * {@inheritdoc}
     */
    public function matchPath($path): bool
    {
        return $this->path === $path || (0 === strpos($path, rtrim($this->path, '/').'/'));
    }

    /**
     * {@inheritdoc}
     */
    public function matchDomain($domain): bool
    {
        // Domain is not set or exact match
        if (!isset($this->domain) || 0 === strcasecmp($domain, $this->domain)) {
            return true;
        }

        // Domain is not an IP address
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) preg_match(sprintf('/\b%s$/i', preg_quote($this->domain)), $domain);
    }

    /**
     * Validates cookie attributes of name, value and maxAge.
     *
     * @return bool
     */
    public function isValid()
    {
        try {
            CookieUtil::validateName($this->name);
            CookieUtil::validateValue($this->value);
            CookieUtil::validateMaxAge($this->maxAge);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate if this cookie should be persisted to storage
     * that survives between requests.
     *
     * @param bool   $allowSessionCookies If we should persist session cookies
     *
     * @return bool
     */
    public function shouldPersist($allowSessionCookies = false)
    {
        if ($this->isExpired() || $allowSessionCookies) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return CookieUtil::toString($this);
    }

    /**
     * Proxy for the native setcookie function - to allow mocking in unit tests so that they do not fail when headers
     * have been sent. But can serve better when used natively to set cookies.
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     * @param string|null $sameSite
     *
     * @return bool
     *
     * @see setcookie
     */
    public static function setcookie($name, $value = "", $expires = 0, $path = "", $domain = "", $secure = false, $httponly = false, $samesite = null)
    {
        if (PHP_VERSION_ID >= 70300) {
            return \setcookie($name, $value, compact('path', 'expires', 'domain', 'secure', 'httponly', 'samesite'));
        }

        return \setcookie($name, $value, $expires, $path . ($samesite ? "; SameSite=$samesite" : ''), $domain, $secure, $httponly);
    }
}
