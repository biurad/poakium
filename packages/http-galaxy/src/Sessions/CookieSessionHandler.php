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

namespace Biurad\Http\Sessions;

use Biurad\Http\Interfaces\CookieFactoryInterface;
use Biurad\Http\Interfaces\CookieInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

class CookieSessionHandler extends AbstractSessionHandler
{
    private CookieFactoryInterface $cookie;

    private ?RequestStack $request;

    private int $maxAge;

    private string $cookieName;

    private bool $gcCalled = false;

    /**
     * Create a new cookie driven handler instance.
     */
    public function __construct(CookieFactoryInterface $cookie, RequestStack $requestStack, int $maxAge = null)
    {
        $this->cookie = $cookie;
        $this->request = $requestStack;
        $this->cookieName = 'sess_'.\hash('md5', __CLASS__);
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

        $session = new Cookie($this->cookieName, \json_encode($value, \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR), new \DateTime('@'.$value['expires']));

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
            $this->cookie->addCookie(Cookie::create($this->cookieName, $cookie, (new \DateTimeImmutable())->add(new \DateInterval('PT'.$this->maxAge.'S'))));

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
            $this->cookie->addCookie(new Cookie($this->cookieName, null, 1));

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|false
     */
    public function gc(int $maxlifetime): int
    {
        // We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;

        return 0;
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
                $this->cookie->addCookie(new Cookie($this->cookieName, null, 1));
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
     * @return Cookie|string|null
     */
    private function getCookie(string $cookieName)
    {
        if (null !== $this->request && $request = $this->request->getMainRequest()) {
            return $request->cookies->get($cookieName);
        }

        return $_COOKIE[$cookieName] ?? null;
    }
}
