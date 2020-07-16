<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache\Interfaces;

use Doctrine\Common\Cache\Cache as DoctrineCache;

/**
 * Interface for cache adapters that should be used in
 * Nette Di container.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface CacheAdapterInterface extends DoctrineCache
{
    /**
     * Set the name for the cache adapter to be used in
     * DI container.
     *
     * @return string
     */
    public static function getName(): string;
}
