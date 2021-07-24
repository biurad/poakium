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

namespace Biurad\Http\Interfaces;

use Biurad\Http\Sessions\Bags\FlashBag;
use Biurad\Http\Sessions\Bags\SessionBag;
use Biurad\Http\Sessions\MetadataBag;
use Psr\Http\Message\ServerRequestInterface;

interface SessionInterface
{
    /**
     * Checks if an attribute is defined.
     */
    public function has(string $name): bool;

    /**
     * Returns an attribute.
     *
     * @param mixed $default The default value if not found
     *
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * Sets an attribute.
     *
     * @param mixed $value
     */
    public function set(string $name, $value): void;

    /**
     * Sets attributes.
     */
    public function replace(array $attributes): void;

    /**
     * Removes an attribute.
     *
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name);

    /**
     * Clears all attributes.
     */
    public function clear(): void;

    /**
     * Starts the session storage.
     *
     * @throws \RuntimeException if session fails to start
     */
    public function start(): bool;

    /**
     * Returns the session ID.
     */
    public function getId(): string;

    /**
     * Sets the session ID.
     */
    public function setId(string $id): void;

    /**
     * Returns the session name.
     */
    public function getName(): string;

    /**
     * Sets the session name.
     */
    public function setName(string $name): void;

    /**
     * Invalidates the current session.
     *
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     */
    public function invalidate(int $lifetime = null): bool;

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     *
     * @param bool $destroy  Whether to delete the old session or leave it to garbage collection
     * @param int  $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                       will leave the system settings unchanged, 0 sets the cookie
     *                       to expire with browser session. Time is in seconds, and is
     *                       not a Unix timestamp.
     */
    public function migrate(bool $destroy = false, int $lifetime = null): bool;

    /**
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save(): void;

    /**
     * Checks if the session was started.
     */
    public function isStarted(): bool;

    /**
     * Registers a SessionBagInterface with the session.
     */
    public function registerBag(SessionBagInterface $bag);

    /**
     * Gets a bag instance by name.
     */
    public function getBag(string $name): SessionBagInterface;

    /**
     * Gets the default sessionbag.
     */
    public function getSessionBag(): SessionBag;

    /**
     * Gets the flashbag from sessions.
     */
    public function getFlashBag(): FlashBag;

    /**
     * Gets session meta.
     */
    public function getMetadataBag(): MetadataBag;

    /**
     * Get the session handler instance.
     */
    public function getHandler(): \SessionHandlerInterface;

    /**
     * Set the request on the handler instance.
     */
    public function setRequestOnHandler(ServerRequestInterface $request): void;
}
