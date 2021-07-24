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

namespace Biurad\Http;

use GuzzleHttp\Psr7\Uri as GuzzleUri;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    use Traits\UriDecorationTrait;

    /** Ported from GuzzleHttp PSR-7 Uri */
    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    public function __construct(string $uri = '')
    {
        $this->uri = new GuzzleUri($uri);
    }

    /**
     * Whether the URI has the default port of the current scheme.
     *
     * `$uri->getPort()` may return the standard port. This method can be used for some non-http/https Uri.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function isDefaultPort(): bool
    {
        $port = $this->getPort();

        return $port === self::DEFAULT_PORTS[$port] ?? null;
    }
}
