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

use Latte;
use Latte\Loaders\StringLoader;
use Nette;

final class LatteRender extends AbstractRender
{
    protected const EXTENSIONS = ['latte'];

    /** @var Latte\Engine */
    protected $latte;

    /**
     * LatteEngine constructor.
     *
     * @param Latte\Engine $engine
     * @param string[]     $extensions
     */
    public function __construct(Latte\Engine $engine, array $extensions = self::EXTENSIONS)
    {
        $this->latte      = $engine;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $parameters = \array_merge($parameters, ['view' => $this]);
        $source     = $this->getLoader()->find($template);

        $this->latte->setLoader(new StringLoader([
            $template => $source->isFile() ? \file_get_contents($source) : $source->getContent(),
        ]));

        return $this->latte->renderToString($template, $parameters);
    }

    /**
     * Registers run-time filter.
     *
     * @param null|string $name
     * @param callable    $callback
     *
     * @return static
     */
    public function addFilter(?string $name, callable $callback)
    {
        $this->latte->addFilter($name, $callback);

        return $this;
    }

    /**
     * Adds new macro.
     *
     * @param string      $name
     * @param Latte\Macro $macro
     *
     * @return static
     */
    public function addMacro(string $name, Latte\Macro $macro)
    {
        $this->latte->addMacro($name, $macro);

        return $this;
    }

    /**
     * Registers run-time function.
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return static
     */
    public function addFunction(string $name, callable $callback)
    {
        $this->latte->addFunction($name, $callback);

        return $this;
    }

    /**
     * Sets translate adapter.
     *
     * @param null|Nette\Localization\ITranslator $translator
     *
     * @return static
     */
    public function setTranslator(?Nette\Localization\ITranslator $translator)
    {
        $this->latte->addFilter('translate', function (Latte\Runtime\FilterInfo $fi, ...$args) use ($translator): string {
            return $translator === null ? $args[0] : $translator->translate(...$args);
        });

        return $this;
    }

    /**
     * Adds new provider.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function addProvider(string $name, $value)
    {
        $this->latte->addProvider($name, $value);

        return $this;
    }

    /**
     * @param null|Latte\Policy $policy
     *
     * @return static
     */
    public function setPolicy(?Latte\Policy $policy)
    {
        $this->latte->setPolicy($policy);

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function setExceptionHandler(callable $callback)
    {
        $this->latte->setExceptionHandler($callback);

        return $this;
    }
}
