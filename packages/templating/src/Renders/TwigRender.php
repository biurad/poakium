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
use Twig\Cache\FilesystemCache;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
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
        $this->environment = $environment ?? new Twig\Environment(new ArrayLoader([], true));
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

            $this->environment->setCache(new FilesystemCache($cacheDir));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function withLoader(Template $loader): RenderInterface
    {
        $this->environment->addFunction(
            new Twig\TwigFunction(
                'template',
                static function (string $template, array $parameters = []) use ($loader): string {
                    return $loader->render($template, $parameters);
                },
                ['is_safe' => ['all']]
            )
        );

        return parent::withLoader($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters): string
    {
        $source = self::loadHtml($template) ?? $template;

        if ($source !== $template || !\file_exists($template)) {
            $loader = new ArrayLoader([$template => $source]);
        } else {
            $loader = new FilesystemLoader([\dirname($template)]);
            $template = \substr($template, strripos($template, '/'));
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

        if ($templateLoader instanceof ChainLoader) {
            $templateLoader->addLoader($loader);

            return;
        }

        if ($loader instanceof ChainLoader) {
            $loader->addLoader($templateLoader);
        } elseif (!$templateLoader instanceof ArrayLoader) {
            $loader = new ChainLoader([$loader, $templateLoader]);
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
