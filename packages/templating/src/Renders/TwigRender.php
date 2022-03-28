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
use Twig\Loader\ChainLoader;
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
        if (\file_exists($template)) {
            if (false !== $cache = $this->environment->getCache()) {
                $mainClass = $this->environment->getTemplateClass($template);

                if (\file_exists($cacheKey = $cache->generateKey($template, $mainClass))) {
                    if (!$this->environment->isAutoReload() || $this->environment->isTemplateFresh($template, $cache->getTimestamp($template))) {
                        $cache->load($cacheKey);
                    }

                    return $this->environment->load($template)->render($parameters);
                }
            }
        } else {
            $source = self::loadHtml($template) ?? $template;
            $template = \substr(\md5($template), 0, 7);
        }

        $this->addLoader(new ArrayLoader([$template => $source ?? \file_get_contents($template)]));

        return $this->environment->render($template, $parameters);
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
        } elseif (!$templateLoader instanceof ArrayLoader || !$templateLoader->isEmpty()) {
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

/**
 * Twig Template loader.
 */
class ArrayLoader implements Twig\Loader\LoaderInterface
{
    /** @var bool */
    private $ignoreKey;

    /** @var array<string,string> */
    private $templates = [];

    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(array $templates = [], bool $ignoreKey = false)
    {
        $this->ignoreKey = $ignoreKey;
        $this->templates = $templates;
    }

    public function isEmpty(): bool
    {
        return empty($this->templates);
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplate(string $name, string $template): void
    {
        $this->templates[$name] = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext(string $name): Twig\Source
    {
        if (!isset($this->templates[$name])) {
            throw new Twig\Error\LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return new Twig\Source($this->templates[$name], $name);
    }

    public function exists(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey(string $name): string
    {
        if (!isset($this->templates[$name])) {
            if (\file_exists($name)) {
                return $name;
            }

            if (!$this->ignoreKey) {
                return $name . ':' . $this->templates[$name];
            }

            throw new Twig\Error\LoaderError(\sprintf('Template "%s" is not defined.', $name));
        }

        return \file_exists($name) ? $name : $name . ':' . $this->templates[$name];
    }

    public function isFresh(string $name, int $time): bool
    {
        if (\file_exists($name)) {
            return \filemtime($name) < $time;
        }

        if (!isset($this->templates[$name])) {
            throw new Twig\Error\LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return true;
    }
}
