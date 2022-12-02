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

namespace Biurad\UI\Helper;

class EscaperHelper extends AbstractHelper
{
    /** @var array<string,callable> */
    protected $escapers = [];

    /** @var array<string,array<int|bool|string,mixed>> */
    protected static $escaperCache = [];

    public function __construct()
    {
        $this->initializeEscapers();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'escape';
    }

    /**
     * Adds an escaper for the given context.
     */
    public function setEscaper(string $context, callable $escaper): void
    {
        $this->escapers[$context] = $escaper;
        self::$escaperCache[$context] = [];
    }

    /**
     * Gets an escaper for a given context.
     *
     * @throws \InvalidArgumentException
     *
     * @return callable A PHP callable
     */
    public function getEscaper(string $context)
    {
        if (!isset($this->escapers[$context])) {
            throw new \InvalidArgumentException(\sprintf('No registered escaper for context "%s".', $context));
        }

        return $this->escapers[$context];
    }

    /**
     * Runs the PHP function htmlspecialchars on the value passed.
     *
     * @param mixed $value The value to escape
     *
     * @return mixed the escaped value
     */
    public function html($value)
    {
        return $this->encode($value, __FUNCTION__);
    }

    /**
     * A function that escape all non-alphanumeric characters
     * into their \xHH or \uHHHH representations.
     *
     * @param mixed $value The value to escape
     *
     * @return mixed the escaped value
     */
    public function js($value)
    {
        return $this->encode($value, __FUNCTION__);
    }

    /**
     * Escapes string for use inside CSS template.
     *
     * @param mixed $value The value to escape
     *
     * @return mixed the escaped value
     */
    public function css($value)
    {
        return $this->encode($value, __FUNCTION__);
    }

    /**
     * Escapes a string by using the current charset.
     *
     * @param mixed $value A variable to escape
     *
     * @return mixed The escaped value
     */
    public function encode($value, string $context = 'html')
    {
        if (\is_numeric($value)) {
            return $value;
        }

        // If we deal with a scalar value, we can cache the result to increase
        // the performance when the same value is escaped multiple times (e.g. loops)
        if (\is_scalar($value)) {
            if (!isset(self::$escaperCache[$context][$value])) {
                self::$escaperCache[$context][$value] = $this->getEscaper($context)($value);
            }

            return self::$escaperCache[$context][$value];
        }

        return $this->getEscaper($context)($value);
    }

    /**
     * Initializes the built-in escapers.
     *
     * Each function specifies a way for applying a transformation to a string
     * passed to it. The purpose is for the string to be "escaped" so it is
     * suitable for the format it is being displayed in.
     *
     * For example, the string: "It's required that you enter a username & password.\n"
     * If this were to be displayed as HTML it would be sensible to turn the
     * ampersand into '&amp;' and the apostrophe into '&aps;'. However if it were
     * going to be used as a string in JavaScript to be displayed in an alert box
     * it would be right to leave the string as-is, but c-escape the apostrophe and
     * the new line.
     *
     * For each function there is a define to avoid problems with strings being
     * incorrectly specified.
     */
    protected function initializeEscapers(): void
    {
        $flags = \ENT_NOQUOTES | \ENT_SUBSTITUTE;

        $this->escapers = [
            'html' =>
                /**
                 * Runs the PHP function htmlspecialchars on the value passed.
                 *
                 * @param string $value The value to escape
                 *
                 * @return string the escaped value
                 */
                function ($value) use ($flags) {
                    // Numbers and Boolean values get turned into strings which can cause problems
                    // with type comparisons (e.g. === or is_int() etc).
                    return \is_string($value) ? \htmlspecialchars($value, $flags, $this->getCharset()) : $value;
                },

            'js' =>
                /**
                 * A function that escape all non-alphanumeric characters
                 * into their \xHH or \uHHHH representations.
                 *
                 * @param string $value The value to escape
                 *
                 * @return string the escaped value
                 */
                function ($value) {
                    if ('UTF-8' != $this->getCharset()) {
                        $value = \iconv($this->getCharset(), 'UTF-8', $value);
                    }

                    $callback = static function ($matches): string {
                        $char = $matches[0];

                        // \xHH
                        if (!isset($char[1])) {
                            return '\\x' . \substr('00' . \bin2hex($char), -2);
                        }

                        // \uHHHH
                        $char = \iconv('UTF-8', 'UTF-16BE', $char);

                        return '\\u' . \substr('0000' . \bin2hex($char), -4);
                    };

                    if (null === $value = \preg_replace_callback('#[^\p{L}\p{N} ]#u', $callback, $value)) {
                        throw new \InvalidArgumentException('The string to escape is not a valid UTF-8 string.');
                    }

                    if ('UTF-8' != $this->getCharset()) {
                        $value = \iconv('UTF-8', $this->getCharset(), $value);
                    }

                    return $value;
                },
            'css' =>
                /**
                 * Escapes string for use inside CSS template.
                 *
                 * @param mixed $value The value to escape
                 */
                function ($value) {
                    // http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
                    return \addcslashes($value, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~");
                },
        ];

        self::$escaperCache = [];
    }
}
