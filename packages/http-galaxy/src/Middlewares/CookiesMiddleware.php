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

namespace BiuradPHP\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use BiuradPHP\Http\Interfaces\CookieInterface;
use Psr\Http\Server\MiddlewareInterface;
use BiuradPHP\Security\Exceptions\DecryptException;
use BiuradPHP\Security\Interfaces\EncrypterInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use BiuradPHP\Http\Interfaces\QueueingCookieInterface as Queueing;

class CookiesMiddleware implements MiddlewareInterface
{
    /**
     * The cookie instance.
     *
     * @var Queueing
     */
    protected $cookies;

    /**
     * The encrypter instance.
     *
     * @var EncrypterInterface|null
     */
    protected $encrypter;

    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = false;

    /**
     * Create a new CookieGuard instance.
     *
     * @param Queueing $cookie
     * @param EncrypterInterface|null $encrypter
     */
    public function __construct(Queueing $cookie, ?EncrypterInterface $encrypter)
    {
        $this->cookies = $cookie;
        $this->encrypter = $encrypter;
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // Decrypt the cookie values on request.
        $response = $handler->handle($this->decrypt($request));

        // Encrypt all cookies queued to the response
        return $this->encrypt($this->cookies, $response);
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * @param  string|array  $name
     * @return void
     */
    public function disableFor($name)
    {
        $this->except = array_merge($this->except, (array) $name);
    }


    /**
     * Decrypt the cookies on the request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function decrypt(Request $request)
    {
        $cookies = [];

        if (null === $this->encrypter) {
            return $request;
        }

        // Handle an incoming request cookie.
        foreach ($request->getCookieParams() as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $cookies[$key] = $this->decryptCookie($key, $cookie);
            } catch (DecryptException $e) {
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
     * @param  string  $name
     * @param  string|array  $cookie
     * @return string|array
     */
    protected function decryptCookie($name, $cookie)
    {
        return is_array($cookie)
            ? $this->decryptArray($cookie)
            : $this->encrypter->decrypt($cookie, static::serialized($name));
    }

    /**
     * Decrypt an array based cookie.
     *
     * @param  array  $cookie
     * @return array
     */
    protected function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $decrypted[$key] = $this->encrypter->decrypt($value, static::serialized($key));
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * @param  Queueing $cookies
     * @param  ResponseInterface  $response
     *
     * @return ResponseInterface
     */
    protected function encrypt(Queueing $cookies, ResponseInterface $response)
    {
        $headers = $response->getHeader('Set-Cookie');

        /** @var CookieInterface $cookie */
        foreach ($cookies->getCookies() as $cookie) {
            if ($this->isDisabled($cookie->getName())) {
                //Nothing to protect
                continue;
            }

            if (null !== $this->encrypter) {
                $cookie = $cookie->withValue(
                    $this->encrypter->encrypt($cookie->getValue(), self::serialized($cookie->getName()))
                );
            }

            $headers[] = (string) $cookie;
        }

        return $headers ? $response->withAddedHeader('Set-Cookie', $headers) : $response;
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * @param  string $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, $this->except);
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @param  string  $name
     * @return bool
     */
    public static function serialized($name)
    {
        return static::$serialize;
    }
}
