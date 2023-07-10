<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Factory;

use Biurad\Http\Interfaces\CookieFactoryInterface;
use Biurad\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * This class is designed to hold a set of Cookies,
 * and Represent singular cookie header value with packing abilities.
 * in order to manage cookies across HTTP requests and responses.
 *
 * Implementation of the class, is quite different from other php
 * cookie handlers. This can send cookies only to Http response
 * headers and not have anything doing with Http request headers.
 *
 * But it can also be implemented to automatically fetch cookies
 * from Http request cookies and return the cookie needed
 * for a specific HTTP request.
 *
 * @see http://wp.netscape.com/newsref/std/cookie_spec.html for some specs.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CookieFactory implements \Countable, \IteratorAggregate, CookieFactoryInterface
{
    /** @var Cookie[] */
    protected $cookies = [];

    /**
     * {@inheritdoc}
     */
    public function hasCookie(string $cookieName): bool
    {
        return isset($this->cookies[$cookieName]);
    }

    /**
     * {@inheritdoc}
     */
    public function addCookie(Cookie ...$cookies): void
    {
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeCookie(string $cookieName): void
    {
        unset($this->cookies[$cookieName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieByName(string $name): ?Cookie
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCookies(): bool
    {
        return !empty($this->cookies);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookies(): array
    {
        $match = static fn (Cookie $matchCookie): bool => true;

        return $this->getMatchingCookies($match);
    }

    /**
     * Removes all cookies.
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->cookies);
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int,Cookie>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator(\array_values($this->cookies));
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingCookies(callable $match): array
    {
        $cookies = [];

        foreach ($this->cookies as $cookie) {
            if ($match($cookie)) {
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function fromResponse(ResponseInterface $response): CookieFactoryInterface
    {
        if ($response instanceof Response) {
            $this->addCookie(...$response->getResponse()->headers->getCookies());
        } else {
            foreach ($response->getHeader('Set-Cookie') as $setCookieString) {
                $cookie = Cookie::fromString($setCookieString);
                $this->cookies[$cookie->getName()] = $cookie;
            }
        }

        return $this;
    }
}
