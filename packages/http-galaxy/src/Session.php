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

namespace Biurad\Http;

use Biurad\Http\Interfaces\SessionBagInterface;
use Biurad\Http\Interfaces\SessionInterface;
use Biurad\Http\Interfaces\SessionStorageInterface;
use Biurad\Http\Sessions\Bags\FlashBag;
use Biurad\Http\Sessions\Bags\SessionBag;
use Biurad\Http\Sessions\Handlers\CookieSessionHandler;
use Biurad\Http\Sessions\MetadataBag;
use Biurad\Http\Sessions\Storage\NativeSessionStorage;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Direct api to php session functionality with segmentation support. Automatically provides access
 * to _SESSION global variable and signs session with user signature.
 *
 * Session will be automatically started upon first request.
 *
 * @see  https://www.owasp.org/index.php/Session_Management_Cheat_Sheet
 */
class Session implements SessionInterface
{
    /** @var \SessionHandlerInterface */
    protected $saveHandler;

    /** @var SessionStorageInterface */
    protected $storage;

    /** @var string */
    private $attributeName;

    /** @var string */
    private $flashName;

    public function __construct(SessionStorageInterface $storage = null)
    {
        $this->storage = $storage ?? new NativeSessionStorage();

        $attributes = new SessionBag();
        $this->attributeName = $attributes->getName();
        $this->storage->registerBag($attributes);

        $flashes = new FlashBag();
        $this->flashName = $flashes->getName();
        $this->storage->registerBag($flashes);
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        return $this->storage->start();
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->storage->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return $this->getSessionBag()->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name, $default = null)
    {
        return $this->getSessionBag()->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, $value): void
    {
        $this->getSessionBag()->add($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes): void
    {
        $this->getSessionBag()->replace($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name)
    {
        return $this->getSessionBag()->remove($name);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->getSessionBag()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->storage->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): void
    {
        if ($this->storage->getId() !== $id) {
            $this->storage->setId($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->storage->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->storage->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(int $lifetime = null): bool
    {
        $this->storage->clear();

        return $this->migrate(true, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(bool $destroy = false, int $lifetime = null): bool
    {
        return $this->storage->regenerate($destroy, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        $this->storage->save();
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->getSessionBag()->getIterator();
    }

    /**
     * Returns the number of attributes.
     */
    public function count(): int
    {
        return $this->getSessionBag()->count();
    }

    /**
     * Get the underlying session handler implementation.
     */
    public function getHandler(): \SessionHandlerInterface
    {
        $saveHandler = $this->saveHandler;

        if (null === $saveHandler && $this->storage instanceof NativeSessionStorage) {
            return $this->storage->getSaveHandler();
        }

        return $saveHandler;
    }

    /**
     * Registers session save handler as a PHP session handler.
     *
     * To use internal PHP session save handlers, override this method using ini_set with
     * session.save_handler and session.save_path e.g.
     *
     *     ini_set('session.save_handler', 'files');
     *     ini_set('session.save_path', '/tmp');
     *
     * or pass in a \SessionHandler instance which configures session.save_handler in the
     * constructor, for a template see NativeFileSessionHandler.
     *
     * @see https://php.net/session-set-save-handler
     * @see https://php.net/sessionhandlerinterface
     * @see https://php.net/sessionhandler
     *
     * @param \SessionHandlerInterface|null $saveHandler
     *
     * @throws \InvalidArgumentException
     */
    public function setHandler(\SessionHandlerInterface $handler): void
    {
        $this->saveHandler = $handler;

        if ($this->storage instanceof NativeSessionStorage) {
            $this->storage->setSaveHandler($handler);
        } else {
            if (\headers_sent() || \PHP_SESSION_ACTIVE === \session_status()) {
                return;
            }

            if ($this->saveHandler instanceof \SessionHandlerInterface) {
                \session_set_save_handler($this->saveHandler, false);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataBag(): MetadataBag
    {
        return $this->storage->getMetadataBag();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag): void
    {
        $this->storage->registerBag($bag);
    }

    /**
     * {@inheritdoc}
     */
    public function getBag($name): SessionBagInterface
    {
        return $this->storage->getBag($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionBag(): SessionBag
    {
        return $this->getBag($this->attributeName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFlashBag(): FlashBag
    {
        return $this->getBag($this->flashName);
    }

    /**
     * Set the request on the handler instance.
     */
    public function setRequestOnHandler(ServerRequestInterface $request): void
    {
        $saveHandler = $this->saveHandler;

        if ($this->storage instanceof NativeSessionStorage) {
            $saveHandler = $this->storage->getSaveHandler();
        }

        if ($saveHandler instanceof CookieSessionHandler) {
            $saveHandler->setRequest($request);
        }
    }
}
