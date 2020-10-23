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
    protected $directory;

    /**
     * @param StorageInterface     $storage
     * @param string               $directory The directory where to store the cache files
     * @param null|LoggerInterface $logger
     */
    public function __construct(StorageInterface $storage, string $directory, ?LoggerInterface $logger = null)
    {
        $this->logger    = $logger;
        $this->storage   = $storage;
        $this->directory = \rtrim($directory, '\/') . '/';
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
        $dir  = $this->directory . \substr($key, 0, 2);
        $file = \substr($key, 2) . '.ui.phtml';
        $path = $dir . \DIRECTORY_SEPARATOR . $file;

        if (\is_file($path) && !$this->isFresh($template, @filemtime($path))) {
            if (null !== $this->logger) {
                $this->logger->debug('Fetching template from cache.', ['name' => $template]);
            }

            return \file_get_contents($path);
        }

        if (null === $storage = $this->storage->load($template)) {
            return null;
        }

        $this->write($path, \file_exists($storage) ? \file_get_contents($storage) : $storage);

        if (null !== $this->logger) {
            $this->logger->debug('Storing template in cache.', ['name' => $template]);
        }

        return \file_get_contents($path);
    }

    /**
     * @param string $key
     * @param string $content
     */
    private function write(string $key, string $content): void
    {
        $dir = \dirname($key);

        if (!\is_dir($dir)) {
            if (false === @\mkdir($dir, 0777, true)) {
                \clearstatcache(true, $dir);

                if (!\is_dir($dir)) {
                    throw new RuntimeException(\sprintf('Unable to create the cache directory (%s).', $dir));
                }
            }
        } elseif (!\is_writable($dir)) {
            throw new RuntimeException(\sprintf('Unable to write in the cache directory (%s).', $dir));
        }

        $tmpFile = \tempnam($dir, \basename($key));

        if (false !== @\file_put_contents($tmpFile, $content) && @\rename($tmpFile, $key)) {
            @\chmod($key, 0666 & ~\umask());

            // Compile cached file into bytecode cache
            if (\function_exists('opcache_invalidate') && \filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN)) {
                @\opcache_invalidate($key, true);
            } elseif (\function_exists('apc_compile_file')) {
                apc_compile_file($key);
            }

            return;
        }

        throw new RuntimeException(\sprintf('Failed to write cache file "%s".', $key));
    }

    /**
     * Returns true if the template is still fresh.
     *
     * Besides checking the loader for freshness information,
     * this method also checks if the enabled extensions have
     * not changed.
     *
     * @param string $name
     * @param int $time The last modification time of the cached template
     */
    private function isFresh(string $name, int $time): bool
    {
        if (null === $path = $this->storage->load($name)) {
            return false;
        }

        return \filemtime($path) < $time;
    }
}
