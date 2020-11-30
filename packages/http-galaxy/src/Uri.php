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
     *
     * @return bool
     */
    public function isDefaultPort(): bool
    {
        return $this->getPort() === null || GuzzleUri::isDefaultPort($this);
    }

    /**
     * Whether the URI is absolute, i.e. it has a scheme.
     *
     * An instance of UriInterface can either be an absolute URI or a relative reference. This method returns true
     * if it is the former. An absolute URI has a scheme. A relative reference is used to express a URI relative
     * to another URI, the base URI. Relative references can be divided into several forms:
     * - network-path references, e.g. '//example.com/path'
     * - absolute-path references, e.g. '/path'
     * - relative-path references, e.g. 'subpath'
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     *
     * @see https://tools.ietf.org/html/rfc3986#section-4
     */
    public function isAbsolute(): bool
    {
        return GuzzleUri::isAbsolute($this);
    }

    /**
     * Whether the URI is a network-path reference.
     *
     * A relative reference that begins with two slash characters is termed an network-path reference.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     *
     * @see https://tools.ietf.org/html/rfc3986#section-4.2
     */
    public function isNetworkPathReference(): bool
    {
        return GuzzleUri::isNetworkPathReference($this);
    }

    /**
     * Whether the URI is a absolute-path reference.
     *
     * A relative reference that begins with a single slash character is termed an absolute-path reference.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     *
     * @see https://tools.ietf.org/html/rfc3986#section-4.2
     */
    public function isAbsolutePathReference(): bool
    {
        return GuzzleUri::isAbsolutePathReference($this);
    }

    /**
     * Whether the URI is a relative-path reference.
     *
     * A relative reference that does not begin with a slash character is termed a relative-path reference.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     *
     * @see https://tools.ietf.org/html/rfc3986#section-4.2
     */
    public function isRelativePathReference(): bool
    {
        return GuzzleUri::isRelativePathReference($this);
    }

    /**
     * Whether the URI is a same-document reference.
     *
     * A same-document reference refers to a URI that is, aside from its fragment
     * component, identical to the base URI. When no base URI is given, only an empty
     * URI reference (apart from its fragment) is considered a same-document reference.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param null|UriInterface $base An optional base URI to compare against
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.4
     */
    public function isSameDocumentReference(UriInterface $base = null): bool
    {
        return GuzzleUri::isSameDocumentReference($this, $base);
    }
}
