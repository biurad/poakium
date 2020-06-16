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

namespace BiuradPHP\Cache;

use Throwable;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Output caching helper.
 */
class OutputHelper
{
    /** @var null|FastCache */
    private $cache;

    /** @var string */
    private $key;

    public function __construct(FastCache $cache, $key)
    {
        $this->cache = $cache;
        $this->key   = $key;
        \ob_start();
    }

    /**
     * Stops and saves the cache.
     *
     * @param array $dependencies
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function end(array $dependencies = []): void
    {
        if (null === $this->cache) {
            throw new InvalidArgumentException('Output cache has already been saved.');
        }
        $this->cache->save($this->key, \ob_get_flush(), $dependencies);

        $this->cache = null;
    }
}
