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

namespace Biurad\Error;

/**
 * A class type that represents either success or failure.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Result implements ResultType
{
    public function __construct(private mixed $value)
    {
    }

    public static function new(mixed $value): static
    {
        return new static($value);
    }

    /**
     * {@inheritdoc}
     */
    public function isOk(callable $match = null): bool
    {
		if ($this->value instanceof \Throwable) {
			return false;
		}

		return null === $match ? true : $match($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function isError(callable $match = null): bool
    {
		if (!$this->value instanceof \Throwable) {
			return false;
		}

		return null === $match ? true : $match($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function ok(): mixed
    {
		return !$this->value instanceof \Throwable ? $this->value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function error(): ?\Throwable
    {
        return $this->value instanceof \Throwable ? $this->value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $match): ResultType
    {
        $this->value = $match($this->value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function or(Result $result): ResultType
    {
        return !$this->value instanceof \Throwable ? $this : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): mixed
    {
        return !$this->value instanceof \Throwable ? $this->value : throw $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrapOr(mixed $value): mixed
    {
        return !$this->value instanceof \Throwable ? ($this->value ?? $value) : throw $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrapError(): \Throwable
    {
        if ($this->value instanceof \Throwable) {
            return $this->value;
        }

        return new Exception(\sprintf('Panics with a value of type "%s".', \get_debug_type($this->value)));
    }

    /**
     * {@inheritdoc}
     */
    public function expect(string $e): mixed
    {
        if ($this->value instanceof \Throwable) {
            throw new Exception(\sprintf('Panics with `%s: %s`', $e, $this->value->getMessage()), $this->value->getCode(), $this->value->getPrevious());
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function expectError(\Throwable|string $e): void
    {
        if ($e instanceof \Throwable) {
            throw $e;
        }

        if ($this->value instanceof \Throwable) {
            throw new Exception(\sprintf('Panics with `%s: %s`', $e, $this->value->getMessage()), $this->value->getCode(), $this->value->getPrevious());
        }

        throw new Exception(\sprintf('Panics with `%s`', $e));
    }
}
