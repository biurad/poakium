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
interface ResultType
{
    /**
     * Returns true if the result is Ok.
     *
     * @param callable<mixed> $match If value inside of it matches a predicate
     */
    public function isOk(callable $match = null): bool;

    /**
     * Returns true if the result is Throwable.
     *
     * @param callable<mixed> $match If value inside of it matches a predicate
     */
    public function isError(callable $match = null): bool;

    /**
     * Returns a nullable value, and discarding the error, if any.
     */
    public function ok(): mixed;

    /**
     * Returns a nullable exception, and discarding the success value, if any.
     */
    public function error(): ?\Throwable;

    /**
     * Converts the value into a new one or Throwable.
     *
     * @param callable<mixed> $match Replace value inside of it
     */
    public function map(callable $match): ResultType;

    /**
     * Returns $result if Error else returns the ok value.
     */
    public function or(Result $result): ResultType;

    /**
     * Returns the ok value else throw an exception if error.
     */
    public function unwrap(): mixed;

    /**
     * Return the ok value else if nullable return new value.
     */
    public function unwrapOr(mixed $value): mixed;

    /**
     * Returns the error exception else return a runtime exception with ok value.
     */
    public function unwrapError(): \Throwable;

    /**
     * Returns the ok value else throw an exception with a reason you expect.
     */
    public function expect(string $e): mixed;

    /**
     * Throws an exception regardless of the ok value contained.
     */
    public function expectError(string|\Throwable $e): void;
}

