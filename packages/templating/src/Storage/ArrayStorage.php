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

namespace Biurad\UI\Storage;

use Biurad\UI\Exceptions\LoaderException;
use Biurad\UI\Interfaces\StorageInterface;

/**
 * Load templates from an array.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ArrayStorage implements StorageInterface
{
    /** @var string[] */
    protected $templates = [];

    /**
     * @param string[] $templates An array of templates
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        if (!$templates = \json_decode($location)) {
            throw new LoaderException(\sprintf('Cannot use [%s] for templates loading.', $location));
        }

        $this->templates = \array_merge($this->templates, $templates);
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template, array $namespaces): ?string
    {
        $loadedTemplate = $namespaces[$template] ?? $this->templates[$template] ?? null;

        if ($loadedTemplate instanceof \Stringable || \is_string($loadedTemplate)) {
            return (string) $loadedTemplate;
        }

        if (null !== $loadedTemplate) {
            throw new LoaderException(\sprintf('Failed to load "%s" as it\'s source isn\'t stringable.', $template));
        }

        return $loadedTemplate;
    }
}
