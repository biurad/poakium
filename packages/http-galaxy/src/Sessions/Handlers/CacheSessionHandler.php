<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Sessions\Handlers;

use Psr\SimpleCache\CacheInterface;

class CacheSessionHandler extends AbstractSessionHandler
{
    /**
     * The cache repository instance.
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    /**
     * Session Name
     *
     * @var string
     */
    protected $sessionOpen;

    /**
     * The number of minutes to store the data in the cache.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cache driven handler instance.
     *
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param null|int|string                 $minutes
     */
    public function __construct(CacheInterface $cache, $minutes = null)
    {
        $this->cache = $cache;

        // convert expiration time to a Unix timestamp
        $minutes       = !\is_numeric($minutes) ? \str_replace('-', '', \time() - \strtotime($minutes)) : $minutes;
        $this->minutes = $minutes ?: (int) \ini_get('session.gc_maxlifetime');
    }

    /**
     * Returns true when the current session exists but expired according to session.gc_maxlifetime.
     *
     * Can be used to distinguish between a new session and one that expired due to inactivity.
     *
     * @return bool Whether current session expired
     */
    public function isSessionExpired()
    {
        if (@$this->sessionOpen < \time() - $this->minutes) {
            //Session flash expired
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        $this->sessionOpen = \time();

        return parent::open($savePath, $sessionName);
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
        $exists = $this->cache->has($sessionId);

        if (!(bool) $exists) {
            return true;
        }

        return (bool) $this->cache->delete($sessionId);
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
     * @inheritDoc
     */
    protected function doRead($sessionId)
    {
        return (string) $this->cache->get($sessionId);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite($sessionId, $data)
    {
        return $this->cache->set($sessionId, $data, $this->minutes);
    }
}
