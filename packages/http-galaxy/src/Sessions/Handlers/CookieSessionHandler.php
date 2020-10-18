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
use Biurad\Http\Interfaces\QueueingCookieInterface;
use Biurad\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class CookieSessionHandler extends AbstractSessionHandler
{
    /**
     * The cookie jar instance.
     *
     * @var QueueingCookieInterface
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
     * Create a new cookie driven handler instance.
     *
     * @param QueueingCookieInterface $cookie
     * @param int $minutes
     */
    public function __construct(QueueingCookieInterface $cookie, $minutes = null)
    {
        $this->cookie = $cookie;

        // convert expiration time to a Unix timestamp
        $minutes       = !\is_numeric($minutes) ? \strtotime($minutes) : $minutes;
        $this->minutes = $minutes ?? (int) \ini_get('session.gc_maxlifetime');
    }

    /**
     * {@inheritdoc}
     */
    public function doRead($sessionId)
    {
        $value = '';

        $sessionCookies = $this->request->getCookieParams();

        if (isset($sessionCookies[$sessionId]) && !empty($sessionCookies)) {
            $value = $sessionCookies[$sessionId];
        }

        if (null !== ($decoded = \json_decode($value, true)) && \is_array($decoded)) {
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
        $secured = \method_exists($this->request, 'isSecured')
            ? $this->request->isSecured()
            : \array_key_exists('HTTPS', $this->request->getServerParams());

        $value = \json_encode(['data' => $data, 'expires' => $this->minutes]);

        $session  = new Cookie([
            'Name'     => $sessionId,
            'Value'    => $value,
            'Domain'   => '',
            'Path'     => '/',
            'Max-Age'  => $this->minutes,
            'Secure'   => $secured,
            'Discard'  => false,
            'HttpOnly' => !$secured,
            'SameSite' => null,
        ]);

        if ($this->request instanceof ServerRequest && $this->request->hasCookie($sessionId)) {
            $session = $this->cookie->getCookieByName($sessionId);
            $session->setValue($value);
        }

        $this->cookie->addCookie($session);

        return true;
    }

    /**
     * @return bool
     */
    public function updateTimestamp($sessionId, $sessionData)
    {
        return $this->write($sessionId, $sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($sessionId)
    {
        $this->cookie->unqueueCookie($sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
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
}
