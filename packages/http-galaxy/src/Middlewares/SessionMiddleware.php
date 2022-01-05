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

use Biurad\Http\Request as HttpRequest;
use Biurad\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionUtils;

class SessionMiddleware implements MiddlewareInterface
{
    // request attribute
    public const ATTRIBUTE = Session::class;

    /** @var callable(): SessionInterface */
    protected $session;

    /**
     * @var array<string, mixed>
     */
    private $sessionOptions;

    /**
     * Create a new session middleware.
     *
     * @param array<string,mixed> $sessionOptions
     */
    public function __construct(callable $sessionCallback, array $sessionOptions = [])
    {
        $this->session = $sessionCallback;
        $this->sessionOptions = $sessionOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        if ($request instanceof HttpRequest) {
            if (!$request->getRequest()->hasSession(true)) {
                // This variable prevents calling `$this->getSession()` twice in case the Request (and the below factory) is cloned
                $sess = null;
                $request->getRequest()->setSessionFactory(function () use (&$sess, $request) {
                    if (!$sess) {
                        $sess = ($this->session)();
                    }

                    /*
                    * For supporting sessions in php runtime with runners like roadrunner or swoole the session
                    * cookie need read from the cookie bag and set on the session storage.
                    */
                    if ($sess && !$sess->isStarted()) {
                        $sessionId = $request->getCookieParams()[$sess->getName()] ?? '';
                        $sess->setId($sessionId);
                    }

                    return $sess;
                });
            }

            $session = $request->getRequest()->getSession();
        } elseif (null === $session = $request->getAttribute(Session::class)) {
            $request = $request->withAttribute(static::ATTRIBUTE, $session = ($this->session)());
        }

        $response = $handler->handle($request);

        if ($session->isStarted()) {
            /*
             * Saves the session, in case it is still open, before sending the response/headers.
             *
             * This ensures several things in case the developer did not save the session explicitly:
             *
             *  * If a session save handler without locking is used, it ensures the data is available
             *    on the next request, e.g. after a redirect. PHPs auto-save at script end via
             *    session_register_shutdown is executed after fastcgi_finish_request. So in this case
             *    the data could be missing the next request because it might not be saved the moment
             *    the new request is processed.
             *  * A locking save handler (e.g. the native 'files') circumvents concurrency problems like
             *    the one above. we ensure the session is not blocked longer than needed.
             *  * When regenerating the session ID no locking is involved in PHPs session design. See
             *    https://bugs.php.net/bug.php?id=61470 for a discussion. So in this case, the session must
             *    be saved anyway before sending the headers with the new session ID. Otherwise session
             *    data could get lost again for concurrent requests with the new ID. One result could be
             *    that you get logged out after just logging in.
             *
             * This Middleware should be executed as one of the last Middlewares, so that previous Middlewares
             * can still operate on the open session. This prevents the overhead of restarting it.
             * Middlewares after closing the session can still work with the session as usual because
             * Biurad session implementation starts the session on demand. So writing to it after
             * it is saved will just restart it.
             */
            $session->save();

            /*
             * For supporting sessions in php runtime with runners like roadrunner or swoole the session
             * cookie need to be written on the response object and should not be written by PHP itself.
             */
            $sessionName = $session->getName();
            $sessionId = $session->getId();
            $sessionOptions = $this->getSessionOptions($this->sessionOptions);
            $sessionCookiePath = $sessionOptions['cookie_path'] ?? '/';
            $sessionCookieDomain = $sessionOptions['cookie_domain'] ?? null;
            $sessionCookieSecure = $sessionOptions['cookie_secure'] ?? false;
            $sessionCookieHttpOnly = $sessionOptions['cookie_httponly'] ?? true;
            $sessionCookieSameSite = $sessionOptions['cookie_samesite'] ?? Cookie::SAMESITE_LAX;

            SessionUtils::popSessionCookie($sessionName, $sessionId);
            $requestSessionCookieId = $request->getCookieParams()[$sessionName] ?? null;

            if ($requestSessionCookieId && ($session instanceof Session ? $session->isEmpty() : empty($session->all()))) {
                $cookie = new Cookie(
                    $sessionName,
                    null,
                    1,
                    $requestSessionCookieId,
                    $sessionCookiePath,
                    $sessionCookieDomain,
                    $sessionCookieSecure,
                    $sessionCookieHttpOnly,
                    $sessionCookieSameSite
                );

                if ($response instanceof Response) {
                    $response = $response->withCookie($cookie);
                } else {
                    $response = $response->withAddedHeader('Set-Cookie', (string) $cookie);
                }
            } elseif ($sessionId !== $requestSessionCookieId) {
                $expire = 0;
                $lifetime = $sessionOptions['cookie_lifetime'] ?? null;

                if ($lifetime) {
                    $expire = \time() + $lifetime;
                }

                $cookie = new Cookie(
                    $sessionName,
                    $sessionId,
                    $expire,
                    $sessionCookiePath,
                    $sessionCookieDomain,
                    $sessionCookieSecure,
                    $sessionCookieHttpOnly,
                    false,
                    $sessionCookieSameSite
                );

                if ($response instanceof Response) {
                    $response = $response->withCookie($cookie);
                } else {
                    $response = $response->withAddedHeader('Set-Cookie', (string) $cookie);
                }
            }
        }

        return $response;
    }

    private function getSessionOptions(array $sessionOptions): array
    {
        $mergedSessionOptions = [];

        foreach (\session_get_cookie_params() as $key => $value) {
            $mergedSessionOptions['cookie_' . $key] = $value;
        }

        foreach ($sessionOptions as $key => $value) {
            // do the same logic as in the NativeSessionStorage
            if ('cookie_secure' === $key && 'auto' === $value) {
                continue;
            }
            $mergedSessionOptions[$key] = $value;
        }

        return $mergedSessionOptions;
    }
}
