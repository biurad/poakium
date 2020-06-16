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

namespace BiuradPHP\Http;

use BiuradPHP\Http\Concerns\IpUtils;
use BiuradPHP\Http\Interfaces\RequestMatcherInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * RequestMatcher compares a pre-defined set of checks against a Request instance.
 *
 * PSR-7 equivalent of Symfony's RequestMatcher
 *
 * Based on https://github.com/symfony/httpfoundation/blob/master/RequestMatcher.php by Fabien
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Matcher implements RequestMatcherInterface
{
    /**
     * @var null|string
     */
    private $path;

    /**
     * @var null|string
     */
    private $host;

    /**
     * @var null|int
     */
    private $port;

    /**
     * @var string[]
     */
    private $methods = [];

    /**
     * @var string[]
     */
    private $ips = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var string[]
     */
    private $schemes = [];

    /**
     * @param null|string|string[] $methods
     * @param null|string|string[] $ips
     * @param null|string|string[] $schemes
     */
    public function __construct(
        string $path = null,
        string $host = null,
        $methods = null,
        $ips = null,
        array $attributes = [],
        $schemes = null,
        int $port = null
    ) {
        $this->matchPath($path);
        $this->matchHost($host);
        $this->matchMethod($methods);
        $this->matchIps($ips);
        $this->matchScheme($schemes);
        $this->matchPort($port);

        foreach ($attributes as $k => $v) {
            $this->matchAttribute($k, $v);
        }
    }

    /**
     * Adds a check for the HTTP scheme.
     *
     * @param null|string|string[] $scheme An HTTP scheme or an array of HTTP schemes
     */
    public function matchScheme($scheme): void
    {
        $this->schemes = null !== $scheme ? \array_map('strtolower', (array) $scheme) : [];
    }

    /**
     * Adds a check for the URL host name.
     */
    public function matchHost(?string $regexp): void
    {
        $this->host = $regexp;
    }

    /**
     * Adds a check for the the URL port.
     *
     * @param null|int $port The port number to connect to
     */
    public function matchPort(?int $port): void
    {
        $this->port = $port;
    }

    /**
     * Adds a check for the URL path info.
     */
    public function matchPath(?string $regexp): void
    {
        $this->path = $regexp;
    }

    /**
     * Adds a check for the client IP.
     *
     * @param string $ip A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     */
    public function matchIp(string $ip): void
    {
        $this->matchIps($ip);
    }

    /**
     * Adds a check for the client IP.
     *
     * @param null|string|string[] $ips A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     */
    public function matchIps($ips): void
    {
        $this->ips = null !== $ips ? (array) $ips : [];
    }

    /**
     * Adds a check for the HTTP method.
     *
     * @param null|string|string[] $method An HTTP method or an array of HTTP methods
     */
    public function matchMethod($method): void
    {
        $this->methods = null !== $method ? \array_map('strtoupper', (array) $method) : [];
    }

    /**
     * Adds a check for request attribute.
     */
    public function matchAttribute(string $key, string $regexp): void
    {
        $this->attributes[$key] = $regexp;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Request $request)
    {
        $requestUri = $request->getUri();

        if ($this->schemes && !\in_array($requestUri->getScheme(), $this->schemes, true)) {
            return false;
        }

        if ($this->methods && !\in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }

        foreach ($this->attributes as $key => $pattern) {
            if (!\preg_match('{' . $pattern . '}', $request->getAttribute($key))) {
                return false;
            }
        }

        $uri      = $requestUri->getPath();
        $basePath = \dirname($request->getServerParams()['SCRIPT_NAME']);

        if (\strpos($uri, $basePath) !== false) {
            $uri = \strlen($basePath) > 1 ? \substr($uri, \strlen($basePath)) ?? '/' : $uri;
        }

        if (null !== $this->path && !\preg_match('{' . $this->path . '}', \rawurldecode($uri))) {
            return false;
        }

        if (null !== $this->host && !\preg_match('{' . $this->host . '}i', $requestUri->getHost())) {
            return false;
        }

        if (null !== $this->port && 0 < $this->port && $requestUri->getPort() !== $this->port) {
            return false;
        }

        if (\method_exists($request, 'remoteAddress') && IpUtils::checkIp($request->remoteAddress(), $this->ips)) {
            return true;
        }

        // Note to future implementors: add additional checks above the
        // foreach above or else your check might not be run!
        return 0 === \count($this->ips);
    }
}
