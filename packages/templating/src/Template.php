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

namespace Biurad\UI;

use Biurad\UI\Exceptions\LoaderException;
use Biurad\UI\Interfaces\CacheInterface;
use Biurad\UI\Interfaces\RenderInterface;
use Biurad\UI\Interfaces\StorageInterface;
use Biurad\UI\Interfaces\TemplateInterface;

/**
 * The template render resolver.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class Template implements TemplateInterface
{
    /** @var StorageInterface */
    private $storage;

    /** @var string|null */
    private $cacheDir;

    /** @var array<int,RenderInterface> */
    private $renders = [];

    /** @var array<string,mixed> */
    private $globals = [];

    /** @var array<string,array<int,string>> */
    private $namespaces = [];

    /** @var array<string,array<int,string>> */
    private $loadedTemplates = [];

    /** @var array<string,bool> */
    private $loadedNamespaces = [];

    public function __construct(StorageInterface $storage, string $cacheDir = null)
    {
        $this->storage = $storage;
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal(string $name, $value): void
    {
        $this->globals[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobal(): array
    {
        return $this->globals;
    }

    /**
     * {@inheritdoc}
     */
    public function addNamespace(string $namespace, $hints): void
    {
        $hints = \is_array($hints) ? $hints : [$hints];
        $this->namespaces[$namespace] = \array_merge($this->namespaces[$namespace] ?? [], $hints);
    }

    /**
     * {@inheritdoc}
     */
    public function addRender(RenderInterface ...$renders): void
    {
        foreach ($renders as $render) {
            if ($render instanceof CacheInterface) {
                $render->withCache($this->cacheDir);
            }

            $this->renders[] = $render->withLoader($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRenders(): array
    {
        return $this->renders;
    }

    /**
     * Get a template render by its supported file extension.
     */
    public function &getRender(string $byFileExtension): RenderInterface
    {
        foreach ($this->renders as &$renderLoader) {
            if (\in_array($byFileExtension, $renderLoader->getExtensions(), true)) {
                return $renderLoader;
            }
        }

        throw new LoaderException(\sprintf('Could not find a render for file extension "%s".', $byFileExtension));
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters = []): string
    {
        $loadedTemplate = $this->find($template, $renderLoader);

        if (null === $loadedTemplate) {
            throw new LoaderException(\sprintf('Unable to load template for "%s", file does not exist.', $template));
        }

        return $renderLoader->render($loadedTemplate, \array_merge($this->globals, $parameters));
    }

    /**
     * Find the template file that exist, then render it contents.
     *
     * @param array<int,string> $templates
     * @param array<string,mixed> $parameters
     */
    public function renderTemplates(array $templates, array $parameters): ?string
    {
        foreach ($templates as $template) {
            try {
                return $this->render($template, $parameters);
            } catch (LoaderException $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $template, RenderInterface &$render = null): ?string
    {
        $requestRender = 2 === \func_num_args();

        if (isset($this->loadedTemplates[$template])) {
            /** @var int $renderOffset */
            [$loadedTemplate, $renderOffset] = $this->loadedTemplates[$template];

            if ($requestRender) {
                $render = $this->renders[$renderOffset];
            }

            return $loadedTemplate;
        }

        if (\str_contains($template, static::NS_SEPARATOR)) {
            [$namespace, $template] = $this->findInNameSpace($template);
        }

        foreach ($this->renders as $offset => $renderLoader) {
            $loadedTemplate = $this->findInStorage($template, $renderLoader);

            if (null !== $loadedTemplate) {
                $this->loadedTemplates[$namespace ?? $template] = [$loadedTemplate, $offset];

                if ($requestRender) {
                    $render = $renderLoader;
                }

                return $loadedTemplate;
            }
        }

        return null;
    }

    /**
     * Find the given view from storage.
     */
    private function findInStorage(string $template, RenderInterface $renderLoader): ?string
    {
        $requestHtml = null;

        if (\str_starts_with($template, 'html:')) {
            [$requestHtml, $template] = ['html:', \substr($template, 5)];
        }

        if (\file_exists($template)) {
            $templateExt = \pathinfo($template, \PATHINFO_EXTENSION);

            if (\in_array($templateExt, $renderLoader->getExtensions(), true)) {
                return $requestHtml . $template;
            }
        } else {
            $template = \str_replace(['\\', '.'], '/', $template);

            foreach ($renderLoader->getExtensions() as $extension) {
                $loadedTemplate = $this->storage->load($template . '.' . $extension);

                if (null !== $loadedTemplate) {
                    return $requestHtml . $loadedTemplate;
                }
            }
        }

        return null;
    }

    /**
     * Find the given template from namespaced storages.
     *
     * @throws LoaderException if template not found
     *
     * @return array<int,string>
     */
    private function findInNameSpace(string $template): array
    {
        [$namespace, $template] = \explode(static::NS_SEPARATOR, \ltrim($key = $template, '@#'), 2);

        if (!isset($this->loadedNamespaces[$namespace])) {
            if (!isset($this->namespaces[$namespace])) {
                throw new LoaderException(\sprintf('No hint path(s) defined for [%s] namespace.', $namespace));
            }

            foreach ($this->namespaces[$namespace] as $viewPath) {
                $this->storage->addLocation($viewPath);
            }

            $this->loadedNamespaces[$namespace] = true;
        }

        return [$key, $template];
    }
}
