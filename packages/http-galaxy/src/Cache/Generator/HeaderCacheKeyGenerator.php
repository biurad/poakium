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

namespace Biurad\Http\Cache\Generator;

use Biurad\Http\Interfaces\CacheKeyGeneratorInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Generate a cache key by using HTTP headers.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class HeaderCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * The header names we should take into account when creating the cache key.
     *
     * @var array<int,string>
     */
    private array $headerNames;

    /**
     * @param array<int,string> $headerNames
     */
    public function __construct(array $headerNames)
    {
        $this->headerNames = $headerNames;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(RequestInterface $request): string
    {
        $concatenatedHeaders = [];

        foreach ($this->headerNames as $headerName) {
            $concatenatedHeaders[] = \sprintf(' %s:"%s"', $headerName, $request->getHeaderLine($headerName));
        }

        return $request->getMethod().' '.$request->getUri().\implode('', $concatenatedHeaders).' '.(string) $request->getBody();
    }
}
