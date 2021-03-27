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
use Biurad\UI\Interfaces\TemplateInterface;
use Biurad\UI\Interfaces\RenderInterface;
use Twig;
use Twig\Cache\FilesystemCache;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
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
        $this->environment = $environment ?? new Twig\Environment(new ArrayLoader());
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
    public function withLoader(TemplateInterface $loader): RenderInterface
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
            $source = \file_get_contents($template);
        } else {
            [$template, $source] = ['hello.twig', $template];
        }

        $this->environment->setLoader(new ChainLoader([new ArrayLoader([$template => $source]), $this->environment->getLoader()]));

        return $this->environment->render($template, $parameters);
    }

    public function setCharset(string $charset): void
    {
        $this->environment->setCharset($charset);
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
