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
    /** @var LoggerInterface|null */
    protected $logger;

    /** @var array<int,string> */
    protected $paths;

    /**
     * @param string|string[] $templatePaths An array of paths to look for templates
     */
    public function __construct($templatePaths = [], LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->paths = \is_array($templatePaths) ? $templatePaths : [$templatePaths];
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        $this->paths[] = $location;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $template, array $namespaces): ?string
    {
        $fileFailures = [];

        if (\file_exists($template)) {
            if (null !== $this->logger) {
                $this->logger->debug('Loaded template file.', ['file' => $template]);
            }

            return $template;
        }

        foreach (($namespaces ?: $this->paths) as $path) {
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
}
