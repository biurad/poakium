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

use Biurad\Http\Utils\CookieUtil;
use Biurad\Http\Utils\SessionUtils;

/**
 * This abstract session handler provides a generic implementation
 * of the PHP 7.0 SessionUpdateTimestampHandlerInterface,
 * enabling strict and lazy session handling.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    /** @var string */
    private $sessionName;

    /** @var string */
    private $prefetchId;

    /** @var mixed */
    private $prefetchData;

    /** @var string */
    private $newSessionId;

    /** @var string|null */
    private $igbinaryEmptyData;

    /** @var bool */
    protected $gcCalled = false;

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        $this->sessionName = $sessionName;

        if (!\headers_sent() && !\ini_get('session.cache_limiter') && '0' !== \ini_get('session.cache_limiter')) {
            \header(\sprintf('Cache-Control: max-age=%d, private, must-revalidate', 60 * (int) \ini_get('session.cache_expire')));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function validateId($sessionId): bool
    {
        $this->prefetchData = $this->read($sessionId);
        $this->prefetchId = $sessionId;

        if (\PHP_VERSION_ID < 70317 || (70400 <= \PHP_VERSION_ID && \PHP_VERSION_ID < 70405)) {
            // work around https://bugs.php.net/79413
            foreach (\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                if (
                    !isset($frame['class']) &&
                    isset($frame['function']) &&
                    \in_array($frame['function'], ['session_regenerate_id', 'session_create_id'], true)
                ) {
                    return '' === $this->prefetchData;
                }
            }
        }

        return '' !== $this->prefetchData;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string
    {
        if (null !== $this->prefetchId) {
            $prefetchId = $this->prefetchId;
            $prefetchData = $this->prefetchData;
            $this->prefetchId = $this->prefetchData = null;

            if ($prefetchId === $sessionId || '' === $prefetchData) {
                $this->newSessionId = '' === $prefetchData ? $sessionId : null;

                return $prefetchData;
            }
        }

        $data = $this->doRead($sessionId);
        $this->newSessionId = '' === $data ? $sessionId : null;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data): bool
    {
        if (null === $this->igbinaryEmptyData) {
            // see https://github.com/igbinary/igbinary/issues/146
            $this->igbinaryEmptyData = \function_exists('igbinary_serialize') ? \igbinary_serialize([]) : '';
        }

        if ('' === $data || $this->igbinaryEmptyData === $data) {
            return $this->destroy($sessionId);
        }
        $this->newSessionId = null;

        return $this->doWrite($sessionId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        if (!\headers_sent() && \filter_var(\ini_get('session.use_cookies'), \FILTER_VALIDATE_BOOLEAN)) {
            if (!$this->sessionName) {
                throw new \LogicException(\sprintf('Session name cannot be empty, did you forget to call "parent::open()" in "%s"?.', static::class));
            }

            $cookie = SessionUtils::popSessionCookie($this->sessionName, $sessionId);

            /*
             * We send an invalidation Set-Cookie header (zero lifetime)
             * when either the session was started or a cookie with
             * the session name was sent by the client (in which case
             * we know it's invalid as a valid session cookie would've
             * started the session).
             */
            if (null === $cookie || isset($_COOKIE[$this->sessionName])) {
                CookieUtil::setcookie(
                    $this->sessionName,
                    '',
                    0,
                    \ini_get('session.cookie_path'),
                    \ini_get('session.cookie_domain'),
                    \filter_var(\ini_get('session.cookie_secure'), \FILTER_VALIDATE_BOOLEAN),
                    \filter_var(\ini_get('session.cookie_httponly'), \FILTER_VALIDATE_BOOLEAN)
                );
            }
        }

        return $this->newSessionId === $sessionId || $this->doDestroy($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): bool
    {
        // We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;

        return true;
    }

    abstract protected function doRead(string $sessionId): string;

    abstract protected function doWrite(string $sessionId, string $data): bool;

    abstract protected function doDestroy(string $sessionId): bool;
}
