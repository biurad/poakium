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

namespace Biurad\Http\Middlewares;

use Biurad\Http\Cookie;
use Biurad\Http\Interfaces\CookieFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;

/**
 * Handle request cookies.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class CookiesMiddleware implements MiddlewareInterface
{
    // request attribute
    public const ATTRIBUTE = Cookie::class;

    /**
     * Queued Cookie storage.
     *
     * @var CookieFactoryInterface
     */
    private $cookieJar;

    /**
     * A callable encrypter
     *
     * @var null|callable(string|null,bool)
     */
    private $encrypter;

    /**
     * @param CookieFactoryInterface $cookieJar
     * @param null|callable          $encrypter
     */
    public function __construct(CookieFactoryInterface $cookieJar, ?callable $encrypter = null)
    {
        $this->cookieJar = $cookieJar;
        $this->encrypter = $encrypter;
    }

    /**
     * {@inheritDoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // Decrypt the cookie values on request.
        $response = $handler->handle($this->resolveCookies($request));

        // Encrypt all cookies queued to the response
        return $this->getCookies($request, $response);
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function getCookies(Request $request, ResponseInterface $response): ResponseInterface
    {
        $headers = $response->getHeader('Set-Cookie');

        foreach ($this->cookieJar->getCookies() as $cookie) {
            if ($cookie->isExpired()) {
                continue;
            }

            if (!$cookie->matchesDomain($request->getUri()->getHost())) {
                continue;
            }

            if (!$cookie->matchesPath($request->getUri()->getPath())) {
                continue;
            }

            if ($cookie->getSecure() && ('https' !== $request->getUri()->getScheme())) {
                continue;
            }

            if (null !== $this->encrypter) {
                $cookie->setValue('ecp@' . ($this->encrypter)($cookie->getValue(), true));
            }

            $headers[] = (string) $cookie;
        }

        if (!empty($headers)) {
            $response = $response->withHeader('Set-Cookie', $headers);
        }

        return $response;
    }

    /**
     * Decrypt the cookies on the request.
     *
     * @param Request $request
     *
     * @return Request
     */
    private function resolveCookies(Request $request): Request
    {
        $cookies = [];
        $request = $request->withAttribute(self::ATTRIBUTE, $this->cookieJar);

        if (null === $this->encrypter) {
            return $request;
        }

        // Handle an incoming request cookie.
        foreach ($request->getCookieParams() as $key => $cookie) {
            try {
                $cookies[$key] = $this->decryptCookie($cookie);
            } catch (Throwable $e) {
                // If cookie failed to decrypt, which means the cookie
                // wasn't encrypted. Hence, we will pass the cookie values
                // in raw state.
                $cookies[$key] = $cookie;
            }
        }

        // Send Decrypted cookies to request.
        return $request->withCookieParams($cookies);
    }

    /**
     * Decrypt the given cookie and return the value.
     *
     * @param string                      $name
     * @param array<string,string>|string $cookie
     *
     * @return array<string,string>|string
     */
    private function decryptCookie($cookie)
    {
        if (\is_array($cookie)) {
            return $this->decryptArray($cookie);
        }

        if ('ecp@' === \substr($cookie, 0, 3)) {
            $cookie = ($this->encrypter)(\substr($cookie, 3), false);
        }

        return $cookie;
    }

    /**
     * Decrypt an array based cookie.
     *
     * @param array<string,string> $cookie
     *
     * @return array<string,string>
     */
    private function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (\is_string($value)) {
                $decrypted[$key] = $this->decryptCookie($value);
            }
        }

        return $decrypted;
    }
}
