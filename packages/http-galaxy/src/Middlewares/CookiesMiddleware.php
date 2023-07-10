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

namespace Biurad\Http\Middlewares;

use Biurad\Http\Interfaces\CookieFactoryInterface;
use Biurad\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Handle request cookies.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CookiesMiddleware implements MiddlewareInterface
{
    // request attribute
    public const ATTRIBUTE = Cookie::class;

    /**
     * The names of the cookies that should be deciphered.
     *
     * @var array<int,string>
     */
    protected array $excludeCookies = [];

    private string $prefix;

    private CookieFactoryInterface $cookieJar;

    /** @var callable(string|null,string,bool) */
    private $encipher;

    /**
     * @param callable(string|null,string,bool)|null $encipher A callable encipher, default is base64 encoder
     */
    public function __construct(CookieFactoryInterface $cookieJar, string $prefix = 'bf_cookie_', callable $encipher = null)
    {
        $this->prefix = $prefix;
        $this->cookieJar = $cookieJar;
        $this->encipher = $encipher;
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * @param string ... $names The cookie names
     */
    public function excludeEncodingFor(string ...$names): void
    {
        $this->excludeCookies = \array_merge($this->excludeCookies, $names);
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $cookieParams = [];

        // Handle an incoming request cookie.
        foreach ($request->getCookieParams() as $key => $cookie) {
            if (!\is_string($cookie) || !\str_contains($cookie, $this->prefix)) {
                $cookieParams[$key] = $cookie;

                continue;
            }

            if (null === $this->encipher) {
                $cookieParams[$key] = \rawurldecode($cookie);
            } else {
                // Unpack all cookies into request and decrypt prefixed cookies.
                $cookieParams[$key] = ($this->encipher)(\substr($cookie, \strlen($this->prefix)), $cookie, false);
            }
        }

        $request = $request->withCookieParams($cookieParams);

        if (!$request instanceof Request && null === $request->getAttribute(static::ATTRIBUTE)) {
            $request = $request->withAttribute(static::ATTRIBUTE, $this->cookieJar);
        }

        return $this->encodeResponseSetCookieHeaders($request->getUri(), $handler->handle($request));
    }

    /**
     * Unpack cookies from cookies factory into response.
     */
    public function encodeResponseSetCookieHeaders(UriInterface $requestUri, ResponseInterface $response): ResponseInterface
    {
        $matchCookie = static function (Cookie $cookie) use ($requestUri): bool {
            $cookiePath = $cookie->getPath();
            $cookieDomain = $cookie->getDomain();

            if ('/' === $cookiePath || null === $cookiePath || $cookiePath === $requestUri->getPath()) {
                goto check_domain;
            }

            check_domain:
            if (empty($cookieDomain) || $cookieDomain === $requestUri->getHost()) {
                return true;
            }

            if ('.' === $cookieDomain[0] && 1 === \preg_match('/'.\strtr($cookieDomain, ['.' => '\\.']).'$/', $requestUri->getHost())) {
                return true;
            }

            return false;
        };

        $cookieCollection = $this->cookieJar->fromResponse($response);
        $response = $response->withoutHeader('Set-Cookie'); // Remove Set-Cookie header from response ...

        foreach ($cookieCollection->getMatchingCookies($matchCookie) as $cookie) {
            if (\in_array($cookie->getName(), $this->excludeCookies, true)) {
                continue;
            }

            $cookie = null !== $this->encipher ? $cookie->withRaw()->withValue($this->prefix.($this->encipher)($cookie->getValue(), $cookie->getName(), true)) : $cookie;

            if ($response instanceof Response) {
                $response->getResponse()->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                $response->getResponse()->headers->setCookie($cookie);

                continue;
            }

            $response = $response->withAddedHeader('Set-Cookie', (string) $cookie);
        }

        return $response;
    }
}
