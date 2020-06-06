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
use BiuradPHP\Loader\Locators\ConfigLocator;

/**
 * ConfigFileLoader loads files from ConfigLocator.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ConfigFileLoader implements LoaderInterface
{
    private $loader;

    /**
     * @param ConfigLocator $Locator
     */
    public function __construct(ConfigLocator $Locator = null)
    {
        $this->loader = $Locator ?: new ConfigLocator();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function load($resource, string $type = null): array
    {
        return $this->loader->loadFile($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null): bool
    {
        return 'config' === $type && (file_exists($resource) && is_file($resource));
    }
}
