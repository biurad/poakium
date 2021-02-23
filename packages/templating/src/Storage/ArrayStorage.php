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
        throw new LoaderException(\sprintf('Cannot use [%s] for views loading', $location));
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template): ?string
    {
        return $this->templates[$template] ?? null;
    }
}
