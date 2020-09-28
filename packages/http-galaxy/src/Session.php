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

use ArrayIterator;
use Biurad\Http\Interfaces\SessionBagInterface;
use Biurad\Http\Interfaces\SessionInterface;
use Biurad\Http\Interfaces\SessionStorageInterface;
use Biurad\Http\Sessions\Bags\FlashBag;
use Biurad\Http\Sessions\Bags\SessionBag;
use Biurad\Http\Sessions\Handlers\CookieSessionHandler;
use Biurad\Http\Sessions\MetadataBag;
use Biurad\Http\Sessions\Proxy\AbstractProxy;
use Biurad\Http\Sessions\Proxy\SessionBagProxy;
use Biurad\Http\Sessions\Storage\NativeSessionStorage;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use SessionHandlerInterface;

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
    /**
     * The session handler implementation.
     *
     * @var SessionHandlerInterface
     */
    protected $saveHandler;

    /**
     * The secction storage implementation
     *
     * @var AbstractProxy|SessionStorageInterface
     */
    protected $storage;

    /**
     * The session attributes storage name.
     *
     * @var string
     */
    private $attributeName;

    /**
     * The session flashes storage name.
     *
     * @var string
     */
    private $flashName;

    /** @var array */
    private $data = [];

    /** @var int */
    private $usageIndex = 0;

    /** @var null|callable */
    private $usageReporter;

    public function __construct(?SessionStorageInterface $storage = null, callable $usageReporter = null)
    {
        $this->storage         = $storage ?? new NativeSessionStorage();
        $this->usageReporter   = $usageReporter;

        $attributes          = new SessionBag();
        $this->attributeName = $attributes->getName();
        $this->registerBag($attributes);

        $flashes         = new FlashBag();
        $this->flashName = $flashes->getName();
        $this->registerBag($flashes);
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

    public function &getUsageIndex(): int
    {
        return $this->usageIndex;
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
     * @internal
     */
    public function isEmpty(): bool
    {
        if ($this->isStarted()) {
            ++$this->usageIndex;

            if ($this->usageReporter && 0 <= $this->usageIndex) {
                ($this->usageReporter)();
            }
        }

        foreach ($this->data as &$data) {
            if (!empty($data)) {
                return false;
            }
        }

        return true;
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
     * @return ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return $this->getSessionBag()->getIterator();
    }

    /**
     * Returns the number of attributes.
     *
     * @return int
     */
    public function count()
    {
        return \count($this->getSessionBag());
    }

    /**
     * Get the underlying session handler implementation.
     *
     * @return SessionHandlerInterface
     */
    public function getHandler(): SessionHandlerInterface
    {
        return $this->saveHandler;
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
     * @param null|AbstractProxy|SessionHandlerInterface $saveHandler
     *
     * @throws InvalidArgumentException
     */
    public function setHandler(?SessionHandlerInterface $handler): void
    {
        if (!$handler instanceof SessionHandlerInterface && null !== $handler) {
            throw new InvalidArgumentException('Must implement \SessionHandlerInterface; and not null.');
        }
        $this->saveHandler = $handler;

        if (\headers_sent() || \PHP_SESSION_ACTIVE === \session_status()) {
            return;
        }

        if ($this->saveHandler instanceof SessionHandlerInterface) {
            \session_set_save_handler($this->saveHandler, false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataBag(): MetadataBag
    {
        ++$this->usageIndex;

        if ($this->usageReporter && 0 <= $this->usageIndex) {
            ($this->usageReporter)();
        }

        return $this->storage->getMetadataBag();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag): void
    {
        $this->storage->registerBag(new SessionBagProxy($bag, $this->data, $this->usageIndex, $this->usageReporter));
    }

    /**
     * {@inheritdoc}
     */
    public function getBag($name): SessionBagInterface
    {
        $bag = $this->storage->getBag($name);

        return \method_exists($bag, 'getBag') ? $bag->getBag() : $bag;
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
     *
     * @param ServerRequestInterface $request
     */
    public function setRequestOnHandler(ServerRequestInterface $request): void
    {
        if ($this->saveHandler instanceof CookieSessionHandler) {
            $this->saveHandler->setRequest($request);
        }
    }
}
