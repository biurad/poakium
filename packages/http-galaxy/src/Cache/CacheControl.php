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

namespace Biurad\Http\Cache;

use Psr\Http\Message\ResponseInterface;

class CacheControl
{
    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
     */
    public const HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES = [
        'must_revalidate'  => false,
        'no_cache'         => false,
        'no_store'         => false,
        'no_transform'     => false,
        'public'           => false,
        'private'          => false,
        'proxy_revalidate' => false,
        'max_age'          => true,
        's_maxage'         => true,
        'immutable'        => false,
    ];

    /**
     * Get the value of a parameter in the cache control header.
     *
     * @param ResponseInterface $response
     * @param string            $name     The field of Cache-Control to fetch
     *
     * @return bool|string The value of the directive, true if directive without value, false if directive not present
     */
    public static function getCacheControlDirective(ResponseInterface $response, $name)
    {
        $headers = $response->getHeader('Cache-Control');

        foreach ($headers as $header) {
            if (\preg_match(\sprintf('|%s=?([0-9]+)?|i', $name), $header, $matches)) {
                // return the value for $name if it exists
                if (isset($matches[1])) {
                    return $matches[1];
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Sets the response's cache headers (validation and/or expiration).
     *
     * Available options are: must_revalidate, no_cache, no_store, no_transform, public,
     *      private, proxy_revalidate, max_age, s_maxage, and immutable.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param array<string,string> $options
     * @param ResponseInterface $response
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     *
     * @final
     */
    public static function withCacheControl(array $options, ResponseInterface $response): ResponseInterface
    {
        $cacheControl = [];
        $this->validateOptions($options);

        $cacheControl['max-age']  = isset($options['max_age']) ? \sprintf('=%s', $options['max_age']) : null;
        $cacheControl['s-maxage'] = isset($options['s_maxage']) ? \sprintf('=%s', $options['s_maxage']) : null;

        foreach (self::HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES as $directive => $hasValue) {
            if (!$hasValue && isset($options[$directive])) {
                if ($options[$directive]) {
                    $cacheControl[\str_replace('_', '-', $directive)] = true;
                } else {
                    unset($cacheControl[\str_replace('_', '-', $directive)]);
                }
            }
        }

        if (isset($options['public']) && $options['public']) {
            $cacheControl['public'] = true;
            unset($cacheControl['private']);
        }

        if (isset($options['private']) && $options['private']) {
            $cacheControl['private'] = true;
            unset($cacheControl['public']);
        }

        \ksort($cacheControl);
        $cacheControl = \array_filter($cacheControl);

        $directives = \array_reduce(\array_keys($cacheControl), function ($res, $name) use ($cacheControl) {
            $add = \implode(' ', (array) $cacheControl[$name]);

            return ('' !== $res ? $res . ', ' : '') . \sprintf('%s%s', $name, '1' === $add ? '' : $add);
        }, '');

        return $response->withHeader('Cache-Control', \strtr($directives, ' 1', ''));
    }

    /**
     * @param array<string,string> $options
     */
    private static function validateOptions(array $options): void
    {
        if ($diff = \array_diff(\array_keys($options), \array_keys(self::HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES))) {
            throw new \InvalidArgumentException(
                \sprintf('Response does not support the following options: "%s".', \implode('", "', $diff))
            );
        }
    }
}
