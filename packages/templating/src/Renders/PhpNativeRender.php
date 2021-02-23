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

namespace Biurad\UI\Renders;

use Biurad\UI\Helper\SlotsHelper;
use Biurad\UI\Interfaces\HelperInterface;
use Biurad\UI\Source;

final class PhpNativeRender extends AbstractRender implements \ArrayAccess
{
    protected const EXTENSIONS = ['phtml', 'html', 'php'];

    /** @var string */
    protected $current;

    /** @var HelperInterface[] */
    protected $helpers = [];

    /** @var array<string,null|string> */
    protected $parents = [];

    /** @var string[] */
    protected $stack = [];

    /** @var string */
    protected $charset = 'UTF-8';

    /** @var array<string,callable> */
    protected $escapers = [];

    /** @var array<string,callable> */
    protected static $escaperCache = [];

    /** @var null|Source */
    private $evalTemplate;

    /** @var null|array<string,mixed> */
    private $evalParameters;

    /**
     * PhpNativeEngine constructor.
     *
     * @param string[]          $extensions
     * @param HelperInterface[] $helpers    An array of helper instances
     */
    public function __construct(array $extensions = self::EXTENSIONS, array $helpers = [])
    {
        $this->extensions = $extensions;
        $this->addHelpers($helpers);

        $this->initializeEscapers();

        foreach ($this->escapers as $context => $escaper) {
            $this->setEscaper($context, $escaper);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $source              = $this->getLoader()->find($template);
        $key                 = \hash('sha256', \serialize($source));
        $this->current       = $key;
        $this->parents[$key] = null;

        // Render
        if (false === $content = $this->evaluate($source, $parameters)) {
            throw new \RuntimeException(\sprintf('The template "%s" cannot be rendered.', $template));
        }

        // decorator
        if ($this->parents[$key]) {
            /** @var SlotsHelper */
            $slots = $this->get('slots');

            $this->stack[] = $slots->get('_content');
            $slots->set('_content', $content);

            $content = $this->render($this->parents[$key], $parameters);

            $slots->set('_content', (string) \array_pop($this->stack));
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $offset The helper name
     *
     * @throws InvalidArgumentException if the helper is not defined
     *
     * @return HelperInterface The helper value
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $offset The helper name
     */
    public function offsetExists($offset)
    {
        return isset($this->helpers[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param HelperInterface $offset The helper name
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $offset The helper name
     */
    public function offsetUnset($offset): void
    {
        throw new \LogicException(\sprintf('You can\'t unset a helper (%s).', $offset));
    }

    /**
     * Adds some helpers.
     *
     * @param HelperInterface[] $helpers An array of helper
     */
    public function addHelpers(array $helpers): void
    {
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, \is_int($alias) ? null : $alias);
        }
    }

    /**
     * Sets the helpers.
     *
     * @param HelperInterface[] $helpers An array of helper
     */
    public function setHelpers(array $helpers): void
    {
        $this->helpers = [];
        $this->addHelpers($helpers);
    }

    public function set(HelperInterface $helper, string $alias = null): void
    {
        $this->helpers[$helper->getName()] = $helper;

        if (null !== $alias) {
            $this->helpers[$alias] = $helper;
        }

        $helper->setCharset($this->charset);
    }

    /**
     * Returns true if the helper if defined.
     *
     * @return bool true if the helper is defined, false otherwise
     */
    public function has(string $name)
    {
        return isset($this->helpers[$name]);
    }

    /**
     * Gets a helper value.
     *
     * @throws \InvalidArgumentException if the helper is not defined
     *
     * @return HelperInterface The helper instance
     */
    public function get(string $name)
    {
        if (!isset($this->helpers[$name])) {
            throw new \InvalidArgumentException(\sprintf('The helper "%s" is not defined.', $name));
        }

        return $this->helpers[$name];
    }

    /**
     * Decorates the current template with another one.
     *
     * @param string $template The decorator logical name
     */
    public function extend(string $template): void
    {
        $this->parents[$this->current] = $template;
    }

    /**
     * Escapes a string by using the current charset.
     *
     * @param mixed $value A variable to escape
     *
     * @return mixed The escaped value
     */
    public function escape($value, string $context = 'html')
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
     * Sets the charset to use.
     *
     * @param string $charset
     */
    public function setCharset(string $charset): void
    {
        if ('UTF8' === $charset = \strtoupper($charset)) {
            $charset = 'UTF-8'; // iconv on Windows requires "UTF-8" instead of "UTF8"
        }
        $this->charset = $charset;

        foreach ($this->helpers as $helper) {
            $helper->setCharset($this->charset);
        }
    }

    /**
     * Gets the current charset.
     *
     * @return string The current charset
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Adds an escaper for the given context.
     *
     * @param string   $context
     * @param callable $escaper
     */
    public function setEscaper(string $context, callable $escaper): void
    {
        $this->escapers[$context]     = $escaper;
        self::$escaperCache[$context] = [];
    }

    /**
     * Gets an escaper for a given context.
     *
     * @param string $context
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
     * Evaluates a template.
     *
     * @param Source              $template
     * @param array<string,mixed> $parameters
     *
     * @throws \InvalidArgumentException
     *
     * @return bool|string The evaluated template,or false if the engine is unable to render the template
     */
    protected function evaluate(Source $template, array $parameters = [])
    {
        $this->evalTemplate   = $template;
        $this->evalParameters = $parameters;
        unset($template, $parameters);

        if (isset($this->evalParameters['this'])) {
            throw new \InvalidArgumentException('Invalid parameter (this).');
        }

        if (isset($this->evalParameters['view'])) {
            throw new \InvalidArgumentException('Invalid parameter (view).');
        }

        // the view variable is exposed to the require file below
        $view = $this;

        \extract($this->evalParameters, \EXTR_SKIP);
        $this->evalParameters = null;

        \ob_start();

        if (!$this->evalTemplate->isFile()) {
            eval('; ?>' . $this->evalTemplate . '<?php ;');
            $this->evalTemplate = null;

            return \ob_get_clean();
        }

        require $this->evalTemplate;
        $this->evalTemplate = null;

        return \ob_get_clean();
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
        $flags = \ENT_QUOTES | \ENT_SUBSTITUTE;

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
                    return \is_string($value) ? \htmlspecialchars($value, $flags, $this->getCharset(), false) : $value;
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

                    $callback = function ($matches): string {
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
        ];

        self::$escaperCache = [];
    }
}
