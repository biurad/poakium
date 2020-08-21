<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache;

use Biurad\Cache\Exceptions\InvalidArgumentException;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

final class CacheItem implements CacheItemInterface
{
    /**
     * Reserved characters that cannot be used in a key or tag.
     */
    public const RESERVED_CHARACTERS = '{}()/\@:';

    /** @var string */
    private $key;

    /** @var mixed */
    private $value;

    /** @var bool */
    private $isHit = false;

    /** @var null|float|int */
    private $expiry;

    /** @var int */
    private $defaultLifetime;

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        if (null === $expiration) {
            return $this->setDefaultExpiration();
        }

        if (!$expiration instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Expiration date must implement DateTimeInterface or be null.');
        }

        $this->expiry = (float) $expiration->format('U.u');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        if (null === $time) {
            return $this->setDefaultExpiration();
        }

        if ($time instanceof DateInterval) {
            $interval     = DateTime::createFromFormat('U', '0')->add($time);
            $this->expiry = \microtime(true) + (int) $interval->format('U.u');
        } elseif (\is_int($time)) {
            $this->expiry = $time + \microtime(true);
        } else {
            throw new InvalidArgumentException('Expiration date must be an integer, a DateInterval or null.');
        }

        return $this;
    }

    /**
     * Validates a cache key according to PSR-6 and PSR-16.
     *
     * @param string $key The key to validate
     *
     * @throws InvalidArgumentException When $key is not valid
     *
     * @return string
     */
    public static function validateKey($key): string
    {
        if (!\is_string($key)) {
            throw new InvalidArgumentException('Cache key must be string.');
        }

        if ('' === $key) {
            throw new InvalidArgumentException('Cache key length must be greater than zero.');
        }

        if (false !== \strpbrk($key, self::RESERVED_CHARACTERS)) {
            throw new InvalidArgumentException(
                \sprintf('Cache key "%s" contains reserved characters "%s".', $key, self::RESERVED_CHARACTERS)
            );
        }

        return $key;
    }

    /**
     * @return static
     */
    private function setDefaultExpiration(): self
    {
        $this->expiry = $this->defaultLifetime > 0 ? \microtime(true) + $this->defaultLifetime : null;

        return $this;
    }
}
