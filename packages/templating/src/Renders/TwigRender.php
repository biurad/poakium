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

use Biurad\UI\Exceptions\LoaderException;
use Biurad\UI\Interfaces\CacheInterface as RenderCacheInterface;
use Biurad\UI\Interfaces\RenderInterface;
use Biurad\UI\Template;
use Twig;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Render for Twig templating.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class TwigRender extends AbstractRender implements RenderCacheInterface
{
    protected const EXTENSIONS = ['twig'];

    /** @var Twig\Environment */
    protected $environment;

    /**
     * TwigEngine constructor.
     *
     * @param string[] $extensions
     */
    public function __construct(Twig\Environment $environment = null, array $extensions = self::EXTENSIONS)
    {
        $this->environment = $environment ?? new Twig\Environment(new Twig\Loader\ArrayLoader());
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function withCache(?string $cacheDir): void
    {
        if (null !== $cacheDir) {
            if (false !== $this->environment->getCache()) {
                throw new LoaderException('The Twig render has an existing cache implementation which must be removed.');
            }

            $this->environment->setCache(new Twig\Cache\FilesystemCache($cacheDir));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function withLoader(Template $loader): RenderInterface
    {
        $this->environment->addFilter(new Twig\TwigFilter('template', [$loader, 'find'], ['is_safe' => ['all']]));
        $this->environment->addFunction(new Twig\TwigFunction('template', [$loader, 'render'], ['is_safe' => ['all']]));

        return parent::withLoader($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $source = self::loadHtml($template) ?? $template;

        if ($source !== $template || !\file_exists($template)) {
            $loader = new Twig\Loader\ArrayLoader([$template => $source]);
        } else {
            $loader = new class ($this->loader) extends Twig\Loader\FilesystemLoader {
                /** @var Template */
                private $loader;

                public function __construct(Template $loader, $paths = [], string $rootPath = null)
                {
                    $this->loader = $loader;
                    parent::__construct($paths, $rootPath);
                }

                protected function findTemplate(string $name, bool $throw = true): ?string
                {
                    if (isset($this->cache[$name])) {
                        return $this->cache[$name];
                    }

                    if (isset($this->errorCache[$name])) {
                        if (!$throw) {
                            return null;
                        }

                        throw new Twig\Error\LoaderError($this->errorCache[$name]);
                    }

                    if (!\is_file($name)) {
                        $newName = $this->loader->find($name);

                        if (null === $newName) {
                            $this->errorCache[$name] = \sprintf('The template "%s" is not a valid file path.', $name);

                            if (!$throw) {
                                return null;
                            }

                            throw new Twig\Error\LoaderError($this->errorCache[$name]);
                        }
                        $name = $newName;
                    }

                    return $this->cache[$name] = $name;
                }
            };
        }
        $this->addLoader($loader);

        return $this->environment->load($template)->render($parameters);
    }

    public function setCharset(string $charset): void
    {
        $this->environment->setCharset($charset);
    }

    public function addLoader(LoaderInterface $loader): void
    {
        $templateLoader = $this->environment->getLoader();

        if ($templateLoader instanceof Twig\Loader\ChainLoader) {
            $templateLoader->addLoader($loader);

            return;
        }

        if ($loader instanceof Twig\Loader\ChainLoader) {
            $loader->addLoader($templateLoader);
        } elseif (!$templateLoader instanceof Twig\Loader\ArrayLoader) {
            $loader = new Twig\Loader\ChainLoader([$loader, $templateLoader]);
        }

        $this->environment->setLoader($loader);
    }

    public function addRuntimeLoader(RuntimeLoaderInterface $loader): void
    {
        $this->environment->addRuntimeLoader($loader);
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->environment->addExtension($extension);
    }

    /**
     * @param ExtensionInterface[] $extensions An array of extensions
     */
    public function setExtensions(array $extensions): void
    {
        $this->environment->setExtensions($extensions);
    }

    public function addNodeVisitor(NodeVisitorInterface $visitor): void
    {
        $this->environment->addNodeVisitor($visitor);
    }

    public function addFilter(Twig\TwigFilter $filter): void
    {
        $this->environment->addFilter($filter);
    }

    public function registerUndefinedFilterCallback(callable $callable): void
    {
        $this->environment->registerUndefinedFilterCallback($callable);
    }

    public function addFunction(Twig\TwigFunction $function): void
    {
        $this->environment->addFunction($function);
    }
}
