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

namespace Biurad\Http\Sessions\Storage;

use Biurad\Http\Interfaces\SessionBagInterface;
use Biurad\Http\Interfaces\SessionStorageInterface;
use Biurad\Http\Sessions\Handlers\AbstractSessionHandler;
use Biurad\Http\Sessions\Handlers\StrictSessionHandler;
use Biurad\Http\Sessions\MetadataBag;

/**
 * This provides a base class for session attribute storage.
 *
 * @author Drak <drak@zikula.org>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class NativeSessionStorage implements SessionStorageInterface
{
    /** @var @var SessionBagInterface[] */
    protected $bags = [];

    /** @var bool */
    protected $started = false;

    /** @var bool */
    protected $closed = false;

    /** @var AbstractSessionHandler */
    private $saveHandler;

    /** @var MetadataBag */
    private $metadataBag;

    /**
     * Depending on how you want the storage driver to behave you probably
     * want to override this constructor entirely.
     *
     * List of options for $options array with their defaults.
     *
     * @see https://php.net/session.configuration for options
     * but we omit 'session.' from the beginning of the keys for convenience.
     *
     * ("auto_start", is not supported as it tells PHP to start a session before
     * PHP starts to execute user-land code. Setting during runtime has no effect).
     *
     * cache_limiter, "" (use "0" to prevent headers from being sent entirely).
     * cache_expire, "0"
     * cookie_domain, ""
     * cookie_httponly, ""
     * cookie_lifetime, "0"
     * cookie_path, "/"
     * cookie_secure, ""
     * cookie_samesite, null
     * gc_divisor, "100"
     * gc_maxlifetime, "1440"
     * gc_probability, "1"
     * lazy_write, "1"
     * name, "PHPSESSID"
     * referer_check, ""
     * serialize_handler, "php"
     * use_strict_mode, "0"
     * use_cookies, "1"
     * use_only_cookies, "1"
     * use_trans_sid, "0"
     * upload_progress.enabled, "1"
     * upload_progress.cleanup, "1"
     * upload_progress.prefix, "upload_progress_"
     * upload_progress.name, "PHP_SESSION_UPLOAD_PROGRESS"
     * upload_progress.freq, "1%"
     * upload_progress.min-freq, "1"
     * url_rewriter.tags, "a=href,area=href,frame=src,form=,fieldset="
     * sid_length, "32"
     * sid_bits_per_character, "5"
     * trans_sid_hosts, $_SERVER['HTTP_HOST']
     * trans_sid_tags, "a=href,area=href,frame=src,form="
     */
    public function __construct(array $options = [], \SessionHandlerInterface $handler = null, MetadataBag $metaBag = null)
    {
        if (!\extension_loaded('session')) {
            throw new \LogicException('PHP extension "session" is required.');
        }

        $options += [
            'cache_limiter' => '', // turn off automatic sending of cache headers entirely
            'cache_expire' => 0,
            'use_cookies' => 1,
            'lazy_write' => 1,
            'use_strict_mode' => 1,
            'cookie_samesite' => 'Lax',
        ];

        \session_register_shutdown();

        $this->setMetadataBag($metaBag);
        $this->setOptions($options);
        $this->setSaveHandler($handler);
    }

    /**
     * Gets the save handler instance.
     */
    public function getSaveHandler(): AbstractSessionHandler
    {
        return $this->saveHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if ($this->isActive()) {
            throw new \RuntimeException('Failed to start the session: already started by PHP.');
        }

        if (\filter_var(\ini_get('session.use_cookies'), \FILTER_VALIDATE_BOOLEAN) && \headers_sent($file, $line)) {
            throw new \RuntimeException(\sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line));
        }

        // ok to try and start the session
        if (!\session_start()) {
            throw new \RuntimeException('Failed to start the session.');
        }

        $this->loadSession();

        return true;
    }

    /**
     * Has a session started?
     *
     * @internal
     */
    public function isActive(): bool
    {
        return \PHP_SESSION_ACTIVE === \session_status();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return \session_id();
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): void
    {
        if ($this->isActive()) {
            throw new \LogicException('Cannot change the ID of an active session.');
        }

        \session_id($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return \session_name();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        if ($this->isActive()) {
            throw new \LogicException('Cannot change the name of an active session.');
        }

        \session_name($name);
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $destroy = false, int $lifetime = null): bool
    {
        // Cannot regenerate the session ID for non-active sessions.
        if ($this->isActive() || \headers_sent()) {
            return false;
        }

        if (null !== $lifetime && $lifetime != \ini_get('session.cookie_lifetime')) {
            $this->save();
            \ini_set('session.cookie_lifetime', (string) $lifetime);
            $this->start();
        }

        if ($destroy) {
            $this->metadataBag->stampNew();
        }

        return \session_regenerate_id($destroy);
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        // Store a copy so we can restore the bags in case the session was not left empty
        $session = $_SESSION;

        foreach ($this->bags as $bag) {
            if (empty($_SESSION[$key = $bag->getStorageKey()])) {
                unset($_SESSION[$key]);
            }
        }

        if ([$key = $this->metadataBag->getStorageKey()] === \array_keys($_SESSION)) {
            unset($_SESSION[$key]);
        }

        // Register error handler to add information about the current save handler
        $previousHandler = \set_error_handler(function ($type, $msg, $file, $line) use (&$previousHandler) {
            if (\E_WARNING === $type && 0 === \strpos($msg, 'session_write_close():')) {
                $msg = \sprintf('session_write_close(): Failed to write session data with "%s" handler', \get_class($this->saveHandler));
            }

            return $previousHandler ? $previousHandler($type, $msg, $file, $line) : false;
        });

        try {
            \session_write_close();
        } finally {
            \restore_error_handler();

            // Restore only if not empty
            if ($_SESSION) {
                $_SESSION = $session;
            }
        }

        $this->closed = true;
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        // clear out the bags
        foreach ($this->bags as $bag) {
            $bag->clear();
        }

        // clear out the session
        $_SESSION = [];

        // reconnect the bags to the session
        $this->loadSession();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag): void
    {
        if ($this->started) {
            throw new \LogicException('Cannot register a bag when the session is already started.');
        }

        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getBag(string $name): SessionBagInterface
    {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(\sprintf('The SessionBagInterface "%s" is not registered.', $name));
        }

        if (!$this->started && $this->isActive()) {
            $this->loadSession();
        } elseif (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    public function setMetadataBag(MetadataBag $metaBag = null): void
    {
        $this->metadataBag = $metaBag ?? new MetadataBag();
    }

    /**
     * Gets the MetadataBag.
     */
    public function getMetadataBag(): MetadataBag
    {
        return $this->metadataBag;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Sets session.* ini variables.
     *
     * For convenience we omit 'session.' from the beginning of the keys.
     * Explicitly ignores other ini keys.
     *
     * @param array $options Session ini directives [key => value]
     *
     * @see https://php.net/session.configuration
     */
    public function setOptions(array $options): void
    {
        if (\headers_sent() || $this->isActive()) {
            return;
        }

        $validOptions = \array_flip([
            'cache_expire', 'cache_limiter', 'cookie_domain', 'cookie_httponly',
            'cookie_lifetime', 'cookie_path', 'cookie_secure', 'cookie_samesite',
            'gc_divisor', 'gc_maxlifetime', 'gc_probability',
            'lazy_write', 'name', 'referer_check',
            'serialize_handler', 'use_strict_mode', 'use_cookies',
            'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
            'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
            'upload_progress.freq', 'upload_progress.min_freq', 'url_rewriter.tags',
            'sid_length', 'sid_bits_per_character', 'trans_sid_hosts', 'trans_sid_tags',
        ]);

        foreach ($options as $key => $value) {
            if (isset($validOptions[$key])) {
                \ini_set('url_rewriter.tags' !== $key ? 'session.' . $key : $key, (string) $value);
            }
        }
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
     * @throws InvalidArgumentException
     */
    public function setSaveHandler(\SessionHandlerInterface $saveHandler = null): void
    {
        if (!$saveHandler instanceof \SessionUpdateTimestampHandlerInterface) {
            $saveHandler = new StrictSessionHandler($saveHandler ?? new \SessionHandler());
        }

        $this->saveHandler = $saveHandler;

        if (\headers_sent() || $this->isActive()) {
            return;
        }

        \session_set_save_handler($this->saveHandler, false);
    }

    /**
     * Load the session with attributes.
     *
     * After starting the session, PHP retrieves the session from whatever handlers
     * are set to (either PHP's internal, or a custom save handler set with session_set_save_handler()).
     * PHP takes the return value from the read() handler, unserializes it
     * and populates $_SESSION with the result automatically.
     */
    protected function loadSession(array &$session = null): void
    {
        if (null === $session) {
            $session = &$_SESSION;
        }

        $bags = \array_merge($this->bags, [$this->metadataBag]);

        foreach ($bags as $bag) {
            $key = $bag->getStorageKey();
            $session[$key] = $session[$key] ?? [];

            $bag->initialize($session[$key]);
        }

        $this->started = true;
        $this->closed = false;
    }
}
