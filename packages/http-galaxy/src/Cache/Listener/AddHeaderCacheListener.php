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

namespace Biurad\Http\Cache\Listener;

use Biurad\Http\Interfaces\CacheListenerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Adds a header indicating if the response came from cache.
 *
 * @author Iain Connor <iainconnor@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AddHeaderCacheListener implements CacheListenerInterface
{
    /** @var string */
    private $headerName;

    /**
     * @param string $headerName
     */
    public function __construct($headerName = 'X-Cache')
    {
        $this->headerName = $headerName;
    }

    /**
     * {@inheritdoc}
     */
    public function onCacheResponse(RequestInterface $request, ResponseInterface $response, ?CacheItemInterface $cacheItem): ResponseInterface
    {
        return $response->withHeader($this->headerName, null !== $cacheItem && $cacheItem->isHit() ? 'HIT' : 'MISS');
    }
}
