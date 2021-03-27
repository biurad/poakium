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

use Biurad\UI\Exceptions\RenderException;
use Biurad\UI\Helper\EscaperHelper;
use Biurad\UI\Helper\SlotsHelper;
use Biurad\UI\Interfaces\HelperInterface;
use Biurad\UI\Interfaces\TemplateInterface;

/**
 * A PHP native template render based.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class PhpNativeRender extends AbstractRender implements \ArrayAccess
{
    protected const EXTENSIONS = ['phtml', 'html', 'php'];

    /** @var string */
    protected $current;

    /** @var HelperInterface[] */
    protected $helpers = [];

    /** @var array<string,callable> */
    protected $parents = [];

    /** @var array<int,mixed> */
    protected $stack = [];

    /** @var string */
    protected $charset = 'UTF-8';

    /** @var string|null */
    private $evalTemplate;

    /** @var array<string,mixed>|null */
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
        $this->addHelpers(\array_merge([new SlotsHelper(), new EscaperHelper()], $helpers));
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $this->current = $key = \hash('sha256', $template);

        if (false === $content = $this->evaluate($template, $parameters)) {
            throw new \RuntimeException(\sprintf('The template "%s" cannot be rendered.', $template));
        }

        if (isset($this->parents[$key])) {
            /** @var SlotsHelper */
            $slots = $this->get('slots');

            $this->stack[] = $slots->get('_content');
            $slots->set('_content', $content);

            $content = $this->parents[$key]($parameters);

            $slots->set('_content', (string) \array_pop($this->stack));
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $offset The helper name
     *
     * @throws \InvalidArgumentException if the helper is not defined
     *
     * @return HelperInterface The helper value
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $offset The helper name
     */
    public function offsetExists($offset): bool
    {
        return isset($this->helpers[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param HelperInterface $offset The helper name
     * @param string|null     $value
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
     * @param array<int,HelperInterface> $helpers An array of helper
     */
    public function setHelpers(array $helpers): void
    {
        $this->helpers = [];
        $this->addHelpers($helpers);
    }

    /**
     * Sets a new helper resolve.
     */
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
        $this->parents[$this->current] = function (array $parameters) use ($template): string {
            $templateRender = $this->loader;

            if (!$templateRender instanceof TemplateInterface) {
                throw new RenderException(\sprintf('Extending template with hash "%s" to "%s" failed. Required %s instance.', $this->current, $template, TemplateInterface::class));
            }

            return $templateRender->render($template, $parameters);
        };
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
        return $this->get(__FUNCTION__)->{$context}($value);
    }

    /**
     * Sets the charset to use.
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
     * Evaluates a template.
     *
     * @param array<string,mixed> $parameters
     *
     * @throws \InvalidArgumentException
     *
     * @return bool|string The evaluated template,or false if the engine is unable to render the template
     */
    protected function evaluate(string $template, array $parameters = [])
    {
        $this->evalTemplate = $template;
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
        // the template variable is exposed to the require file below
        $template = $this->loader;

        \extract($this->evalParameters, \EXTR_SKIP);
        $this->evalParameters = null;

        \ob_start();

        if (!\file_exists($this->evalTemplate)) {
            eval('; ?>' . $this->evalTemplate . '<?php ;');
            $this->evalTemplate = null;

            return \ob_get_clean();
        }

        require $this->evalTemplate;
        $this->evalTemplate = null;

        return \ob_get_clean();
    }
}
