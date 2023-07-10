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
 * Generate a cache key from the request method, URI and body.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SimpleGenerator implements CacheKeyGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(RequestInterface $request): string
    {
        $body = (string) $request->getBody();

        if (!empty($body)) {
            $body = ' '.$body;
        }

        return $request->getMethod().' '.(string) $request->getUri().$body;
    }
}
