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

use Biurad\UI\Interfaces\CacheInterface;
use Biurad\UI\Interfaces\RenderInterface;
use Biurad\UI\Template;
use Latte;

/**
 * Render for Latte templating.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class LatteRender extends AbstractRender implements CacheInterface
{
    protected const EXTENSIONS = ['latte'];

    /** @var Latte\Engine */
    protected $latte;

    /**
     * LatteEngine constructor.
     *
     * @param string[] $extensions
     */
    public function __construct(Latte\Engine $engine = null, array $extensions = self::EXTENSIONS)
    {
        $this->latte = $engine ?? new Latte\Engine();
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function withCache(?string $cacheDir): void
    {
        if (null !== $cacheDir) {
            $this->latte->setTempDirectory($cacheDir); // Replace regardless ...
        }
    }

    /**
     * {@inheritdoc}
     */
    public function withLoader(Template $loader): RenderInterface
    {
        $this->latte->addFilter('template', [$loader, 'find'])->addFunction('template', [$loader, 'render']);

        return parent::withLoader($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $source = self::loadHtml($template) ?? $template;

        if ($source !== $template || !\file_exists($template)) {
            $templateLoader = new Latte\Loaders\StringLoader([$template => $source]);
        }

        $latte = $this->latte->setLoader($templateLoader ?? new class ($this->loader) extends Latte\Loaders\FileLoader {
            /** @var Template */
            private $loader;

            public function __construct(Template $loader, ?string $baseDir = null)
            {
                $this->loader = $loader;
                parent::__construct($baseDir);
            }

            public function getContent($fileName): string
            {
                if (!\is_file($fileName)) {
                    $fileName = $this->loader->find($fileName);

                    if (null === $fileName) {
                        throw new Latte\RuntimeException(\sprintf('Missing template file \'%s\'.', $fileName));
                    }
                }

                return parent::getContent($fileName);
            }
        });

        return $latte->renderToString($template, $parameters);
    }

    /**
     * Registers run-time filter.
     *
     * @return $this
     */
    public function addFilter(?string $name, callable $callback)
    {
        $this->latte->addFilter($name, $callback);

        return $this;
    }

    /**
     * Adds new macro.
     *
     * @return $this
     */
    public function addMacro(string $name, Latte\Macro $macro)
    {
        $this->latte->addMacro($name, $macro);

        return $this;
    }

    /**
     * Registers run-time function.
     *
     * @return $this
     */
    public function addFunction(string $name, callable $callback)
    {
        $this->latte->addFunction($name, $callback);

        return $this;
    }

    /**
     * Adds new provider.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addProvider(string $name, $value)
    {
        $this->latte->addProvider($name, $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function setPolicy(?Latte\Policy $policy)
    {
        $this->latte->setPolicy($policy);

        return $this;
    }

    /**
     * @return $this
     */
    public function setExceptionHandler(callable $callback)
    {
        $this->latte->setExceptionHandler($callback);

        return $this;
    }
}
