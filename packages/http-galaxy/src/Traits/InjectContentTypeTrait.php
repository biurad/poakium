<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Traits;

trait InjectContentTypeTrait
{
    /**
     * Inject the provided Content-Type, if none is already present.
     *
     * @return array Headers with injected Content-Type
     */
    private function injectContentType(string $contentType, array $headers): array
    {
        $hasContentType = \array_reduce(\array_keys($headers), function ($carry, $item) {
            return $carry ?: (\strtolower($item) === 'content-type');
        }, false);

        if (!$hasContentType) {
            $headers['content-type'] = [$contentType];
        }

        return $headers;
    }
}
