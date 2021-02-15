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
use Biurad\UI\Interfaces\LoaderInterface;
use Biurad\UI\Interfaces\StorageInterface;
use Biurad\UI\Storage\CacheStorage;

/**
 * Loads and locates view files associated with specific extensions.
 */
final class Loader implements LoaderInterface
{
    /** @var null|Profile */
    protected $profiler;

    /** @var string[] */
    protected $extensions = [];

    /** @var StorageInterface */
    private $storage;

    /** @var array<string,mixed> */
    private $namespaces = [];

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage, ?Profile $profile = null)
    {
        $this->storage  = $storage;
        $this->profiler = $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function addNamespace(string $namespace, $hints): void
    {
        $hints = (array) $hints;

        if (isset($this->namespaces[$namespace])) {
            $hints = \array_merge($this->namespaces[$namespace], $hints);
        }

        $this->namespaces[$namespace] = $hints;
    }

    /**
     * {@inheritdoc}
     */
    public function withExtensions(array $extensions): LoaderInterface
    {
        $loader             = clone $this;
        $loader->extensions = $extensions;

        return $loader;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $template
     */
    public function exists(string $view, string &$template = null): bool
    {
        try {
            if (\strpos($view = \trim($view), static::NS_SEPARATOR) > 0) {
                $template = $this->findNamespacedView($view);

                return true;
            }

            $this->validatePath($view);
            $template = $this->findInStorage($view);

            return true;
        } catch (LoaderException $e) {
            if ("File [{$view}] not found." !== $e->getMessage()) {
                throw $e;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $view): Source
    {
        if (!$this->exists($view, $template)) {
            throw new LoaderException("Unable to load view `$view`, file does not exists.");
        }

        if (null !== $this->profiler) {
            $profile = new Profile(Profile::TEMPLATE, $view);
        }

        try {
            return new Source($template, $this->storage instanceof CacheStorage);
        } finally {
            if (null !== $this->profiler) {
                $this->profiler->addProfile($profile->leave());
            }
        }
    }

    /**
     * @return null|Profile
     */
    public function getProfile(): ?Profile
    {
        return $this->profiler;
    }

    /**
     * Get the path to a template with a named path.
     *
     * @param string $name
     *
     * @return string
     */
    private function findNamespacedView(string $name): string
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        try {
            $this->storage->addLocation($this->namespaces[$namespace]);
        } catch (LoaderException $e) {
            // Do nothing ...
        }

        return $this->findInStorage($view);
    }

    /**
     * Find the given view from storage.
     *
     * @param string $template
     *
     * @throws LoaderException if template not found
     *
     * @return string
     */
    private function findInStorage(string $template): string
    {
        foreach ($this->getPossibleFiles($template) as $file) {
            if (null !== $found = $this->storage->load($file)) {
                return $found;
            }
        }

        throw new LoaderException("File [{$template}] not found.");
    }

    /**
     * Get an array of possible view files.
     *
     * @param string $name
     *
     * @return string[]
     */
    private function getPossibleFiles(string $name): array
    {
        return \array_map(function ($extension) use ($name): string {
            //Cutting extra symbols (see Twig)
            return \preg_replace(
                '#/{2,}#',
                '/',
                \str_replace(['\\', '.'], '/', $name)
            ) . '.' . $extension;
        }, $this->extensions);
    }

    /**
     * Get the segments of a template with a named path.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    private function parseNamespaceSegments(string $name): array
    {
        $segments    = \explode(static::NS_SEPARATOR, $name);
        $segments[0] = \str_replace(['@', '#'], '', $segments[0]);

        if (\count($segments) !== 2) {
            throw new \InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (!isset($this->namespaces[$segments[0]])) {
            throw new \InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Make sure view filename is OK. Same as in twig.
     *
     * @param string $path
     *
     * @throws LoaderException
     */
    private function validatePath(string $path): void
    {
        if (empty($path)) {
            throw new LoaderException('A view path is empty');
        }

        if (false !== \strpos($path, "\0")) {
            throw new LoaderException('A view path cannot contain NULL bytes');
        }

        $path  = \ltrim($path, '/');
        $parts = \explode('/', $path);
        $level = 0;

        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new LoaderException(\sprintf('Looks like you try to load a view outside configured directories (%s)', $path));
            }
        }
    }
}
