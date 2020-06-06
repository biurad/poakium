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

use BiuradPHP\Loader\Interfaces\AliasTypeInterface;
use BiuradPHP\Loader\Interfaces\LoaderInterface;

/**
 * AliasLoader loads namesapces and class aliases from ConfigLocator.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
class AliasLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array|AliasTypeInterface
     */
    public function load($resource, string $type = null)
    {
        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null): bool
    {
        if ('alias' === $type && is_array($resource)) {
            return true;
        }

        return $resource instanceof AliasTypeInterface;
    }
}
