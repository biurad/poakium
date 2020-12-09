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
use Psr\Http\Message\ServerRequestInterface;

class CookieSessionHandler extends AbstractSessionHandler
{
    /**
     * The cookie jar instance.
     *
     * @var CookieFactoryInterface
     */
    protected $cookie;

    /**
     * The server request instance.
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    protected $minutes;

    /**
     * The session cookie name.
     *
     * @var string
     */
    protected $name;

    /**
     * Whether gc() has been called
     *
     * @var bool
     */
    private $gcCalled = false;

    /**
     * Create a new cookie driven handler instance.
     *
     * @param CookieFactoryInterface $cookie
     * @param int                     $minutes
     */
    public function __construct(CookieFactoryInterface $cookie, $minutes = null)
    {
        $this->cookie = $cookie;
        $this->name   = 'sess_' . \hash('md5', __CLASS__);

        // convert expiration time to a Unix timestamp
        $minutes       = !\is_numeric($minutes) ? \strtotime($minutes) : $minutes;
        $this->minutes = $minutes ?? (int) \ini_get('session.gc_maxlifetime');
    }

    /**
     * {@inheritdoc}
     */
    public function doRead($sessionId)
    {
        $value = [];

        if (null !== $cookie = $this->getCookie($this->name)) {
            $value = \json_decode($cookie, true);
        }

        if (null !== ($decoded = $value[$sessionId] ?? null) && \is_array($decoded)) {
            if ($decoded['expires'] > 0 && $decoded['expires'] < \time()) {
                return $decoded['data'];
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($sessionId, $data)
    {
        $secured = 'https' === $this->request->getUri()->getScheme();
        $value   = \json_encode([$sessionId => ['data' => $data, 'expires' => $this->minutes]]);

        $session  = new Cookie([
            'Name'     => $this->name,
            'Value'    => $value,
            'Domain'   => null,
            'Path'     => '/',
            'Max-Age'  => $this->minutes,
            'Secure'   => $secured,
            'SameSite' => null,
        ]);

        $this->cookie->addCookie($session);

        return true;
    }

    /**
     * @return bool
     */
    public function updateTimestamp($sessionId, $sessionData)
    {
        $expiry = \time() + $this->minutes;
        $value  = [];

        if (null !== $cookie = $this->getCookie($this->name)) {
            $value = \json_decode($cookie, true);
        }

        if (isset($value[$sessionId])) {
            $this->cookie->addCookie($this->name, $cookie, null, '/', null, $expiry);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($sessionId)
    {
        $value = [];

        if (null !== $cookie = $this->getCookie($this->name)) {
            $value = \json_decode($cookie, true);
        }

        if (isset($value[$sessionId])) {
            $this->cookie->addCookie($this->name, \json_encode([$sessionId => '']), null, '/', null, 0);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->gcCalled) {
            $this->gcCalled = false;
            $value          = [];

            if (null !== $cookie = $this->getCookie($this->name)) {
                $value = \json_decode($cookie, true);
            }

            // delete the session records that have expired
            foreach ($value as $sessionId => $decoded) {
                if ($decoded['expires'] > 0 && $decoded['expires'] > \time()) {
                    $this->cookie->addCookie($this->name, \json_encode([$sessionId => '']), null, '/', null, 0);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        // We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;

        return true;
    }

    /**
     * Set the request instance.
     *
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the cookie from request if exists
     *
     * @param string $cookieName
     *
     * @return null|CookieInterface|string
     */
    private function getCookie(string $cookieName)
    {
        return $this->request->getCookieParams()[$cookieName] ?? null;
    }
}
