<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Interfaces;

use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Called by the cache plugin with information on the cache status.
 * Provides an opportunity to update the response based on whether the cache was a hit or a miss, or
 * other cache-meta-data.
 *
 * @author Iain Connor <iainconnor@gmail.com>
 */
interface CacheListenerInterface
{
    /**
     * Called before the cache plugin returns the response, with information on whether that response came from cache.
     *
     * @param bool $fromCache Whether the `$response` was from the cache or not.
     *                        Note that checking `$cacheItem->isHit()` is not sufficient to determine this.
     */
    public function onCacheResponse(RequestInterface $request, ResponseInterface $response, ?CacheItemInterface $cacheItem): ResponseInterface;
}
