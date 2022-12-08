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
use Biurad\UI\Storage\ChainStorage;

/**
 * The template render resolver.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class Template
{
    /** Namespace separator */
    public const NS_SEPARATOR = '::';

    /** @var array<string,mixed> */
    public $globals = [];

    /** @var StorageInterface */
    private $storage;

    /** @var string|null */
    private $cacheDir;

    /** @var array<int,RenderInterface> */
    private $renders = [];

    /** @var array<string,array<int,string>> */
    private $namespaces = [];

    /** @var array<string,array<int,mixed>> */
    private $loadedTemplates = [];

    public function __construct(StorageInterface $storage, string $cacheDir = null)
    {
        $this->storage = $storage;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Add a namespace hint to the finder.
     *
     * @param string|string[] $hints list of directories to look into
     */
    public function addNamespace(string $namespace, $hints): void
    {
        if (!\is_array($hints)) {
            $hints = [$hints];
        }
        $this->namespaces[$namespace] = \array_merge($this->namespaces[$namespace] ?? [], $hints);
    }

    /**
     * Adds a new storage system to templating.
     *
     * This can be useful as e.g. cached templates may be fetched
     * from database and used in runtime.
     */
    public function addStorage(StorageInterface $storage): void
    {
        if ($this->storage instanceof ChainStorage) {
            $this->storage->addStorage($storage);
        } else {
            $this->storage = new ChainStorage([$this->storage, $storage]);
        }
    }

    /**
     * Get the storage system used.
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * Attach the view render(s).
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
     * Get all associated view engines.
     *
     * @return array<int,RenderInterface>
     */
    public function getRenders(): array
    {
        return $this->renders;
    }

    /**
     * Get a template render by its supported file extension.
     */
    public function getRender(string $byFileExtension): RenderInterface
    {
        foreach ($this->renders as $renderLoader) {
            if (\in_array($byFileExtension, $renderLoader->getExtensions(), true)) {
                return $renderLoader;
            }
        }

        throw new LoaderException(\sprintf('Could not find a render for file extension "%s".', $byFileExtension));
    }

    /**
     * Renders a template.
     *
     * @param string              $template   A template name or a namespace name to path
     * @param array<string,mixed> $parameters An array of parameters to pass to the template
     *
     * @throws LoaderException if the template cannot be rendered
     *
     * @return string The evaluated template as a string
     */
    public function render(string $template, array $parameters = []): string
    {
        $loadedTemplate = $this->find($template, $renderLoader);

        if (!isset($loadedTemplate, $renderLoader)) {
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
        $renderLoader = null;

        foreach ($templates as $template) {
            $loadedTemplate = $this->find($template, $renderLoader);

            if (isset($loadedTemplate, $renderLoader)) {
                return $renderLoader->render($loadedTemplate, \array_merge($this->globals, $parameters));
            }
        }

        return null;
    }

    /**
     * Get source for given template. Path might include namespace prefix or extension.
     *
     * @param string $template A template name or a namespace name to path
     *
     * @throws LoaderException if unable to load template from namespace
     *
     * @return string|null Expects the template absolute file or null
     */
    public function find(string $template, RenderInterface &$render = null): ?string
    {
        if ($cachedTemplate = &$this->loadedTemplates[$template] ?? null) {
            [$loadedTemplate, $renderOffset] = $cachedTemplate;

            if (2 === \func_num_args()) {
                $render = $this->renders[$renderOffset];
            }

            return $loadedTemplate;
        }

        if (\str_contains($template, self::NS_SEPARATOR)) {
            [$namespace, $template] = \explode(self::NS_SEPARATOR, \ltrim($template, '@#'), 2);

            if (empty($namespaces = $this->namespaces[$namespace] ?? [])) {
                throw new LoaderException(\sprintf('No hint source(s) defined for [%s] namespace.', $namespace));
            }
        }

        if (\str_starts_with($template, 'html:')) {
            [$requestHtml, $template] = ['html:', \substr($template, 5)];
        }

        $templateExt = \pathinfo($template, \PATHINFO_EXTENSION);

        foreach ($this->renders as $offset => $renderLoader) {
            $loadedTemplate = null;

            if (\in_array($templateExt, $extensions = $renderLoader->getExtensions(), true)) {
                $loadedTemplate = $this->storage->load($template, $namespaces ?? []);

                if (null !== $loadedTemplate) {
                    break;
                }
            }

            foreach ($extensions as $extension) {
                $loadedTemplate = $this->storage->load(\str_replace(['\\', '.'], '/', $template) . '.' . $extension, $namespaces ?? []);

                if (null !== $loadedTemplate) {
                    break 2;
                }
            }
        }

        if (isset($loadedTemplate, $renderLoader, $offset)) {
            $cachedTemplate = [$loadedTemplate = ($requestHtml ?? null) . $loadedTemplate, $offset];

            if (2 === \func_num_args()) {
                $render = $renderLoader;
            }
        }

        return $loadedTemplate ?? null;
    }
}
