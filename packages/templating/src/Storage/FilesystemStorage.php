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

/**
 * FilesystemLoader is a loader that read templates from the filesystem.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FilesystemStorage implements StorageInterface
{
    /** @var null|LoggerInterface */
    protected $logger;

    /**
     * The array of active view paths.
     *
     * @var string[]
     */
    protected $paths;

    /**
     * @param string|string[]      $templatePaths An array of paths to look for templates
     * @param null|LoggerInterface $logger
     */
    public function __construct($templatePaths, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->paths  = \array_map([$this, 'resolvePath'], (array) $templatePaths);
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        $this->paths[] = $this->resolvePath($location);
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template): ?string
    {
        $fileFailures = [];

        foreach ($this->paths as $path) {
            if (\file_exists($found = $path . '/' . $template)) {
                if (null !== $this->logger) {
                    $this->logger->debug('Loaded template file.', ['file' => $template]);
                }

                return $found;
            }

            if (null !== $this->logger) {
                $fileFailures[] = $template;
            }
        }

        // only log failures if no template could be loaded at all
        foreach ($fileFailures as $file) {
            if (null !== $this->logger) {
                $this->logger->debug('Failed loading template file.', ['file' => $file]);
            }
        }

        return null;
    }

    /**
     * Resolve the path.
     *
     * @param string $path
     *
     * @return string
     */
    private function resolvePath(string $path): string
    {
        return (new \SplFileInfo($path))->getPathname();
    }
}
