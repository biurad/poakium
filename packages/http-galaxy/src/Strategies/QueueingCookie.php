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

namespace Biurad\Http\Strategies;

use Biurad\Http\Cookie;
use Biurad\Http\Interfaces\CookieInterface;
use Biurad\Http\Interfaces\QueueingCookieInterface;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use SplObjectStorage;
use UnexpectedValueException;

/**
 * This class is designed to hold a set of Cookies,
 * and Represent singular cookie header value with packing abilities.
 * in order to manage cookies across HTTP requests and responses.
 *
 * Implementation of the class, is quite different from other php
 * cookie handlers. This can send cookies only to Http response
 * headers and not have anything doing with Http resquest headers.
 *
 * But it can also be implemented to automatically fetch cookies
 * from Http request cookies and return the cookie needed
 * for a specific HTTP request.
 *
 * @see http://wp.netscape.com/newsref/std/cookie_spec.html for some specs.
 */
class QueueingCookie implements Countable, IteratorAggregate, QueueingCookieInterface
{
    /**
     * The default path (if specified).
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The default domain (if specified).
     *
     * @var string
     */
    protected $domain;

    /**
     * The default secure setting (defaults to false).
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * All of the cookies queued for sending.
     *
     * @var SplObjectStorage
     */
    protected $cookies;

    public function __construct()
    {
        $this->cookies = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function hasCookie(CookieInterface $cookie): bool
    {
        return $this->cookies->contains($cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function addCookie(...$parameters): void
    {
        $cookie = \reset($parameters) instanceof CookieInterface
            ? \reset($parameters) : \call_user_func_array([$this, 'setCookie'], $parameters);

        if (!\assert($cookie instanceof CookieInterface)) {
            throw new UnexpectedValueException(
                \sprintf('Expected cookie to be instance of %s', CookieInterface::class)
            );
        }

        if (!$this->hasCookie($cookie)) {
            $cookies = $this->getMatchingCookies($cookie);

            foreach ($cookies as $matchingCookie) {
                if (
                    $cookie->getValue() !== $matchingCookie->getValue() ||
                    $cookie->getMaxAge() > $matchingCookie->getMaxAge()
                ) {
                    $this->removeCookie($matchingCookie);

                    continue;
                }
            }

            if (null !== $cookie->getValue()) {
                $this->cookies->attach($cookie);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeCookie(CookieInterface $cookie): void
    {
        $this->cookies->detach($cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieByName($name): ?CookieInterface
    {
        // don't allow a non string name
        if ($name === null || !\is_scalar($name)) {
            return null;
        }

        foreach ($this->cookies as $cookie) {
            if ($cookie->getName() !== null && \strcasecmp($cookie->getName(), $name) === 0) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCookies(): bool
    {
        return $this->cookies->count() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookies(): array
    {
        $match = function ($matchCookie) {
            return true;
        };

        return $this->findMatchingCookies($match);
    }

    /**
     * {@inheritdoc}
     */
    public function hasQueuedCookie($CookieName): bool
    {
        return null !== $this->getCookieByName($CookieName);
    }

    /**
     * {@inheritdoc}
     */
    public function unqueueCookie($CookieName): void
    {
        if (!$this->hasQueuedCookie($CookieName)) {
            return;
        }

        $this->removeCookie($this->getCookieByName($CookieName));
    }

    /**
     * Set the default path and domain for the jar.
     *
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return $this
     */
    public function setDefaultPathAndDomain($path, $domain, $secure = false)
    {
        [$this->path, $this->domain, $this->secure] = [$path, $domain, $secure];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingCookies(CookieInterface $cookie): array
    {
        $match = function ($matchCookie) use ($cookie) {
            return $matchCookie->matches($cookie);
        };

        return $this->findMatchingCookies($match);
    }

    /**
     * Removes all cookies.
     */
    public function clear(): void
    {
        $this->cookies = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->cookies->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return clone $this->cookies;
    }

    /**
     * Get the path and domain, or the default values.
     *
     * @param string    $path
     * @param string    $domain
     * @param null|bool $secure
     *
     * @return array
     */
    protected function getPathAndDomain($path, $domain, $secure = null)
    {
        return [$path ?: $this->path, $domain ?: $this->domain, \is_bool($secure) ? $secure : $this->secure];
    }

    /**
     * Create a new cookie instance.
     *
     * @param string                            $name
     * @param string                            $value
     * @param null|string                       $domain
     * @param string                            $path
     * @param int                               $maxAge
     * @param null|DateTimeInterface|int|string $expires
     * @param bool                              $secure
     * @param bool                              $discard
     * @param bool                              $httpOnly
     * @param string                            $sameSite
     *
     * @return CookieInterface
     */
    protected function setCookie(
        $name,
        $value,
        $domain = null,
        $path = '/',
        $maxAge = null,
        $expires = null,
        $secure = false,
        $discard = false,
        $httpOnly = false,
        $sameSite = null
    ): CookieInterface {
        [$path, $domain, $secure] = $this->getPathAndDomain($path, $domain, $secure);

        return new Cookie([
            'Name'     => $name,
            'Value'    => $value,
            'Domain'   => $domain,
            'Path'     => $path,
            'Max-Age'  => $maxAge,
            'Expires'  => $expires,
            'Secure'   => $secure,
            'Discard'  => $discard,
            'HttpOnly' => $httpOnly,
            'SameSite' => $sameSite,
        ]);
    }

    /**
     * Finds matching cookies based on a callable.
     *
     * @param callable $match
     *
     * @return Cookie[]
     */
    protected function findMatchingCookies(callable $match)
    {
        $cookies = [];

        foreach ($this->cookies as $cookie) {
            if ($match($cookie)) {
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }
}
