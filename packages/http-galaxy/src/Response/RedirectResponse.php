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

namespace Biurad\Http\Response;

use Biurad\Http\Exception;
use Biurad\Http\Response;
use Psr\Http\Message\UriInterface;

/**
 * Produce a redirect response.
 */
class RedirectResponse extends Response
{
    /**
     * Produces a redirect response with a Location header and the given status (302 by default).
     *
     * Note: this method overwrites the `location` $headers value.
     *
     * @param string|UriInterface $uri     URI for the Location header
     * @param int                 $status  integer status code for the redirect; 302 by default
     * @param array               $headers array of headers to use at initialization
     */
    public function __construct($uri, int $status = 302, array $headers = [])
    {
        if (!\is_string($uri) && !$uri instanceof UriInterface) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Uri provided to %s MUST be a string or Psr\Http\Message\UriInterface instance; received "%s"',
                __CLASS__,
                \is_object($uri) ? $uri::class : \gettype($uri)
            ));
        }

        $headers['location'] = [(string) $uri];
        parent::__construct($status, $headers, 'php://temp');
    }
}
