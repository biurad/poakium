<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache;

use Psr\Cache\CacheItemInterface;

final class CacheItem implements CacheItemInterface
{
    /**
     * Reserved characters that cannot be used in a key or tag.
     */
    public const RESERVED_CHARACTERS = '{}()/\@:';

    private ?string $key = null;
    private mixed $value = null;
    private bool $isHit = false;
    private float|int|null $expiry = null;
    private int $defaultLifetime = 0;

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
    public function get(): mixed
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
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(\DateTimeInterface|null $expiration): static
    {
        if (null === $expiration) {
            return $this->setDefaultExpiration();
        }

        $this->expiry = (float) $expiration->format('U.u');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(\DateInterval|int|null $time): static
    {
        if (null === $time) {
            return $this->setDefaultExpiration();
        }

        if ($time instanceof \DateInterval) {
            $interval = \DateTime::createFromFormat('U', '0')->add($time);
            $this->expiry = \microtime(true) + (int) $interval->format('U.u');
        } elseif (\is_int($time)) {
            $this->expiry = $time + \microtime(true);
        }

        return $this;
    }

    /**
     * @internal
     */
    public function getExpiry(): ?float
    {
        return $this->expiry;
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
