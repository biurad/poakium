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

namespace Biurad\Http\Sessions;

use Biurad\Http\Interfaces\SessionBagInterface;

/**
 * Metadata container.
 *
 * Adds metadata to the session.
 *
 * @author Drak <drak@zikula.org>
 */
class MetadataBag implements SessionBagInterface
{
    public const CREATED = 'c';

    public const UPDATED = 'u';

    public const LIFETIME = 'l';

    /** @var array */
    protected $meta = [self::CREATED => 0, self::UPDATED => 0, self::LIFETIME => 0];

    /** @var string */
    private $name = '__metadata';

    /** @var string */
    private $storageKey;

    /** @var int */
    private $lastUsed;

    /** @var int */
    private $updateThreshold;

    /**
     * @param string $storageKey      The key used to store bag in the session
     * @param int    $updateThreshold The time to wait between two UPDATED updates
     */
    public function __construct(string $storageKey = '_bf_meta', int $updateThreshold = 0)
    {
        $this->storageKey = $storageKey;
        $this->updateThreshold = $updateThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$array): void
    {
        $this->meta = &$array;

        if (isset($array[self::CREATED])) {
            $this->lastUsed = $this->meta[self::UPDATED];

            $timeStamp = \time();

            if ($timeStamp - $array[self::UPDATED] >= $this->updateThreshold) {
                $this->meta[self::UPDATED] = $timeStamp;
            }
        } else {
            $this->stampCreated();
        }
    }

    /**
     * Gets the lifetime that the session cookie was set with.
     */
    public function getLifetime(): int
    {
        return $this->meta[self::LIFETIME];
    }

    /**
     * Stamps a new session's metadata.
     *
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     */
    public function stampNew(int $lifetime = null): void
    {
        $this->stampCreated($lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    /**
     * Gets the created timestamp metadata.
     *
     * @return int Unix timestamp
     */
    public function getCreated()
    {
        return $this->meta[self::CREATED];
    }

    /**
     * Gets the last used metadata.
     *
     * @return int Unix timestamp
     */
    public function getLastUsed(): int
    {
        return $this->lastUsed;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    private function stampCreated(int $lifetime = null): void
    {
        $this->meta[self::CREATED] = $this->meta[self::UPDATED] = $this->lastUsed = \time();
        $this->meta[self::LIFETIME] = (null === $lifetime) ? \ini_get('session.cookie_lifetime') : $lifetime;
    }
}
