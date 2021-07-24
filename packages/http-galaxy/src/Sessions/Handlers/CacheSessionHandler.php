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

use Psr\Cache\CacheItemPoolInterface;

class CacheSessionHandler extends AbstractSessionHandler
{
    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var int|float */
    private $sessionOpen;

    /** @var string */
    private $sessionId;

    /** @var int */
    private $minutes;

    /**
     * Create a new cache driven handler instance.
     *
     * @param int|string|null $minutes
     */
    public function __construct(CacheItemPoolInterface $cache, $minutes = null)
    {
        $this->cache = $cache;

        if (null === $minutes) {
            $minutes = (int) \ini_get('session.gc_maxlifetime');
        } elseif (!\is_numeric($minutes)) {
            $minutes = \time() - \strtotime($minutes);
        }

        $this->minutes = $minutes;
    }

    /**
     * Returns true when the current session exists but expired according to session.gc_maxlifetime.
     *
     * Can be used to distinguish between a new session and one that expired due to inactivity.
     *
     * @return bool Whether current session expired
     */
    public function isSessionExpired(): bool
    {
        if (@$this->sessionOpen < \time() - $this->minutes) {
            //Session flash expired
            return true;
        }

        return false;
    }

    public function open($savePath, $sessionName): bool
    {
        $this->sessionOpen = \time();

        return parent::open($savePath, $sessionName);
    }

    public function updateTimestamp($sessionId, $sessionData): bool
    {
        return $this->write($sessionId, $sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($sessionId): bool
    {
        $exists = $this->cache->hasItem($sessionId);

        if (!(bool) $exists) {
            return true;
        }

        return (bool) $this->cache->deleteItem($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->gcCalled && null !== $this->sessionId) {
            $this->gcCalled = false;

            $this->cache->deleteItem($this->sessionId);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId): string
    {
        $this->sessionId = $sessionId;

        return (string) $this->cache->getItem($sessionId)->get();
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data): bool
    {
        $item = $this->cache->getItem($sessionId)
            ->expiresAfter($this->minutes);

        return $this->cache->save($item->set($data));
    }
}
