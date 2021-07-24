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

namespace Biurad\Http\Sessions\Handlers;

use Biurad\Http\Cookie;
use Biurad\Http\Interfaces\CookieFactoryInterface;
use Biurad\Http\Interfaces\CookieInterface;
use Biurad\Http\Utils\CookieUtil;
use Psr\Http\Message\ServerRequestInterface;

class CookieSessionHandler extends AbstractSessionHandler
{
    /** @var CookieFactoryInterface */
    private $cookie;

    /** @var ServerRequestInterface|null */
    private $request;

    /** @var int */
    private $maxAge;

    /** @var string */
    private $cookieName;

    /**
     * Create a new cookie driven handler instance.
     */
    public function __construct(CookieFactoryInterface $cookie, int $maxAge = null)
    {
        $this->cookie = $cookie;
        $this->cookieName = 'sess_' . \hash('md5', __CLASS__);
        $this->maxAge = $maxAge ?? (int) \ini_get('session.gc_maxlifetime');
    }

    /**
     * {@inheritdoc}
     */
    public function doRead($sessionId): string
    {
        $value = [];

        if (null !== $cookie = $this->getCookie($this->cookieName)) {
            $value = \json_decode($cookie, true, 512, \JSON_THROW_ON_ERROR);
        }

        if (isset($value[$sessionId])) {
            if (\time() <= $value['expires'] ?? 0) {
                return $value[$sessionId];
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($sessionId, $data): bool
    {
        $value = [$sessionId => $data];

        if (null !== $cookie = $this->getCookie($this->cookieName)) {
            $value += \json_decode($cookie, true, 512, \JSON_THROW_ON_ERROR);
        }

        if (!isset($value['expires'])) {
            $value['expires'] = \time() + $this->maxAge;
        }

        $session = new Cookie($this->cookieName, \json_encode($value, \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR), new \DateTime('@' . $value['expires']));

        $this->cookie->addCookie($session);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        $value = [];

        if (null !== $cookie = $this->getCookie($this->cookieName)) {
            $value = \json_decode($cookie, true, 512, \JSON_THROW_ON_ERROR);
        }

        if (isset($value[$sessionId])) {
            $this->cookie->addCookie((new Cookie($this->cookieName, $cookie))->withMaxAge(CookieUtil::normalizeMaxAge($this->maxAge)));

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($sessionId): bool
    {
        $value = [];

        if (null !== $cookie = $this->getCookie($this->cookieName)) {
            $value = \json_decode($cookie, true, 512, \JSON_THROW_ON_ERROR);
        }

        if (isset($value[$sessionId])) {
            $this->cookie->addCookie((new Cookie($this->cookieName, $cookie))->expire());

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->gcCalled) {
            $this->gcCalled = false;
            $value = [];

            if (null !== $cookie = $this->getCookie($this->cookieName)) {
                $value = \json_decode($cookie, true, 512, \JSON_THROW_ON_ERROR);
            }

            // delete the session records that have expired
            if (isset($value['expires']) && \time() > $value['expires']) {
                $this->cookie->addCookie((new Cookie($this->cookieName, $cookie))->expireWhenBrowserIsClosed());
            }
        }

        return true;
    }

    /**
     * Set the request instance.
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the cookie from request if exists.
     *
     * @return CookieInterface|string|null
     */
    private function getCookie(string $cookieName)
    {
        if (null !== $this->request) {
            return $this->request->getCookieParams()[$cookieName] ?? null;
        }

        return $_COOKIE[$cookieName] ?? null;
    }
}
