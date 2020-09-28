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

use LogicException;
use SessionHandler;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Adds basic `SessionUpdateTimestampHandlerInterface` behaviors to another `SessionHandlerInterface`.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class StrictSessionHandler extends AbstractSessionHandler
{
    /** @var SessionHandlerInterface */
    private $handler;

    private $doDestroy;

    public function __construct(SessionHandlerInterface $handler)
    {
        if ($handler instanceof SessionUpdateTimestampHandlerInterface) {
            throw new LogicException(
                \sprintf(
                    '"%s" is already an instance of "SessionUpdateTimestampHandlerInterface", ' .
                    'you cannot wrap it with "%s".',
                    \get_class($handler),
                    self::class
                )
            );
        }

        $this->handler = $handler;

        $this->wrapper         = ($handler instanceof SessionHandler);
        $this->saveHandlerName = $this->wrapper ? \ini_get('session.save_handler') : 'user';
    }

    /**
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        parent::open($savePath, $sessionName);

        return $this->handler->open($savePath, $sessionName);
    }

    /**
     * @return bool
     */
    public function updateTimestamp($sessionId, $data)
    {
        return $this->write($sessionId, $data);
    }

    /**
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->doDestroy = true;
        $destroyed       = parent::destroy($sessionId);

        return $this->doDestroy ? $this->doDestroy($sessionId) : $destroyed;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return $this->handler->close();
    }

    /**
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return $this->handler->gc($maxlifetime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId)
    {
        return $this->handler->read($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data)
    {
        return $this->handler->write($sessionId, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId)
    {
        $this->doDestroy = false;

        return $this->handler->destroy($sessionId);
    }
}
