<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/biurad-loader
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader;

use BiuradPHP\Loader\Interfaces\LoaderInterface;
use BiuradPHP\Loader\Locators\FileLocator;

/**
 * DirectoryLoader is a recursive loader to go through directories.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class DirectoryLoader implements LoaderInterface
{
    private $loader;

    /**
     * @param ConfigLocator $Locator
     */
    public function __construct(FileLocator $Locator)
    {
        $this->loader = $Locator;
    }

    /**
     * {@inheritdoc}
     */
    public function load($file, string $type = null)
    {
        return $this->loader->findDirectories();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null): bool
    {
        if ('directory' === $type && is_dir($resource)) {
            return true;
        }

        return null === $type && \is_string($resource) && '/' === substr($resource, -1);
    }
}
