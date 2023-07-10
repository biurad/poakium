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

use Psr\Http\Message\RequestInterface;

/**
 * An interface for generate a cache key.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface CacheKeyGeneratorInterface
{
    /**
     * Generate a cache key from a Request.
     */
    public function generate(RequestInterface $request): string;
}
