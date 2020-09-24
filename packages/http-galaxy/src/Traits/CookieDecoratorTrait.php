<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Traits;

use BiuradPHP\Http\Interfaces\CookieInterface;
use BiuradPHP\Http\Utils\CookieUtil;
use InvalidArgumentException;

trait CookieDecoratorTrait
{
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
     * @var null|string
     */
    private $value;

    /**
     * Cookie lifetime. This value specified in seconds and declares period of time in which cookie
     * will expire relatively to current time() value.
     *
     * NOTE: Expires attribute is HTTP 1.0 only and should be avoided.
     *
     * @var null|int
     */
    private $expires;

    /**
     * Cookie maxAge. This value specified in seconds and declares period of time in which cookie
     * will last for.
     *
     * @var null|int
     */
    private $maxAge;

    /**
     * The path on the server in which the cookie will be available on.
     *
     * If set to '/', the cookie will be available within the entire domain. If set to '/foo/',
     * the cookie will only be available within the /foo/ directory and all sub-directories such as
     * /foo/bar/ of domain. The default value is the current directory that the cookie is being set
     * in.
     *
     * @var null|string
     */
    private $path;

    /**
     * The domain that the cookie is available. To make the cookie available on all subdomains of
     * example.com then you'd set it to '.example.com'. The . is not required but makes it
     * compatible with more browsers. Setting it to www.example.com will make the cookie only
     * available in the www subdomain. Refer to tail matching in the spec for details.
     *
     * @var null|string
     */
    private $domain;

    /**
     * Indicates that the cookie should only be transmitted over a secure HTTPS connection from the
     * client. When set to true, the cookie will only be set if a secure connection exists.
     * On the server-side, it's on the programmer to send this kind of cookie only on secure
     * connection
     * (e.g. with respect to $_SERVER["HTTPS"]).
     *
     * @var null|bool
     */
    private $secure;

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
     * {@inheritdoc}
     */
    public function withValue(string $value): CookieInterface
    {
        CookieUtil::validateValue($value);

        $cookie        = clone $this;
        $cookie->value = $value;

        return $cookie;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath(string $path): CookieInterface
    {
        $clone       = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withDomain(string $domain): CookieInterface
    {
        $clone         = clone $this;
        $clone->domain = $domain;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withSecure(bool $secure = true): CookieInterface
    {
        $clone         = clone $this;
        $clone->secure = $secure;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHttpOnly(bool $httpOnly = true): CookieInterface
    {
        $clone           = clone $this;
        $clone->httpOnly = $httpOnly;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withSameSite(?string $sameSite): CookieInterface
    {
        $clone           = clone $this;
        $clone->sameSite = $sameSite;

        return $clone;
    }

    public function withMaxAge(?int $maxAge): CookieInterface
    {
        $clone         = clone $this;
        $clone->maxAge = $maxAge;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withExpires($expires): CookieInterface
    {
        $expires = CookieUtil::normalizeExpires($expires);

        $clone          = clone $this;
        $clone->expires = $expires;

        return $clone;
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
        return $this->name === $cookie->name && $this->domain === $cookie->domain && $this->path === $cookie->path;
    }

    /**
     * {@inheritdoc}
     */
    public function matchPath($path): bool
    {
        return $this->path === $path || (0 === \strpos($path, \rtrim($this->path, '/') . '/'));
    }

    /**
     * {@inheritdoc}
     */
    public function matchDomain($domain): bool
    {
        // Domain is not set or exact match
        if (!isset($this->domain) || 0 === \strcasecmp($domain, $this->domain)) {
            return true;
        }

        // Domain is not an IP address
        if (\filter_var($domain, \FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) \preg_match(\sprintf('/\b%s$/i', \preg_quote($this->domain)), $domain);
    }

    /**
     * Validates cookie attributes of name, value and maxAge.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        try {
            CookieUtil::validateName($this->name);
            CookieUtil::validateValue($this->value);
            CookieUtil::validateMaxAge($this->maxAge);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }
}
