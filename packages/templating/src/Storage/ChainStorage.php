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
 * ChainStorage is a loader that calls other storage loaders to load templates.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ChainStorage implements StorageInterface
{
    /** @var StorageInterface[] */
    protected $loaders = [];

    /**
     * @param StorageInterface[] $storages An array of storage instances
     */
    public function __construct(array $storages = [])
    {
        foreach ($storages as $loader) {
            $this->addStorage($loader);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        throw new LoaderException(\sprintf('Cannot use [%s] for views loading', $location));
    }

    /**
     * Adds a storage loader instance.
     *
     * @param StorageInterface $storage
     */
    public function addStorage(StorageInterface $storage): void
    {
        $this->loaders[] = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template): ?string
    {
        foreach ($this->loaders as $loader) {
            if (null !== $storage = $loader->load($template)) {
                return $storage;
            }
        }

        return null;
    }
}
