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

use Biurad\UI\Interfaces\StorageInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * CacheLoader is a loader that caches other loaders responses
 * on the filesystem.
 *
 * This cache only caches on disk to allow PHP accelerators to cache the opcodes.
 * All other mechanism would imply the use of `eval()`.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CacheStorage implements StorageInterface
{
    /** @var StorageInterface */
    protected $storage;

    /** @var null|LoggerInterface */
    protected $logger;

    /** @var string */
    protected $dir;

    /**
     * @param StorageInterface     $storage
     * @param string               $dir     The directory where to store the cache files
     * @param null|LoggerInterface $logger
     */
    public function __construct(StorageInterface $storage, string $dir, ?LoggerInterface $logger = null)
    {
        $this->logger  = $logger;
        $this->storage = $storage;
        $this->dir     = $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        $this->storage->addLocation($location);
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template): ?string
    {
        $key  = \hash('sha256', $template);
        $dir  = $this->dir . \DIRECTORY_SEPARATOR . \substr($key, 0, 2);
        $file = \substr($key, 2) . '.ui.phtml';
        $path = $dir . \DIRECTORY_SEPARATOR . $file;

        if (\is_file($path)) {
            if (null !== $this->logger) {
                $this->logger->debug('Fetching template from cache.', ['name' => $template]);
            }

            return \file_get_contents($path);
        }

        if (null === $storage = $this->storage->load($template)) {
            return null;
        }

        $content = \file_get_contents($storage);

        if (!\is_dir($dir) && !@\mkdir($dir, 0777, true) && !\is_dir($dir)) {
            throw new RuntimeException(\sprintf('Cache Loader was not able to create directory "%s".', $dir));
        }

        \file_put_contents($path, $content);

        if (null !== $this->logger) {
            $this->logger->debug('Storing template in cache.', ['name' => $template]);
        }

        return \file_get_contents($path);
    }
}
