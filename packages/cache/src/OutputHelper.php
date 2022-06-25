<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\Cache;

use Biurad\Cache\Exceptions\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Output caching helper.
 */
class OutputHelper
{
    /** @var FastCache|null */
    private $cache;

    /** @var string */
    private $key;

    /**
     * @param mixed $key
     */
    public function __construct(FastCache $cache, $key)
    {
        $this->cache = $cache;
        $this->key = $key;

        \ob_start();
    }

    /**
     * Stops and saves the cache.
     *
     * @param callable $callback
     *
     * @throws InvalidArgumentException
     */
    public function end(callable $callback = null, ?float $beta = null): void
    {
        if (null === $this->cache) {
            throw new InvalidArgumentException('Output cache has already been saved.');
        }

        $this->cache->save(
            $this->key,
            static function (CacheItemInterface $item) use ($callback) {
                if (null !== $callback) {
                    $callback($item);
                }

                return \ob_get_flush();
            },
            $beta
        );

        $this->cache = null;
    }
}
