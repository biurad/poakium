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

namespace Biurad\Http\Strategies;

use Biurad\Http\Interfaces\RequestMatcherInterface;
use Biurad\Http\ServerRequest;
use Biurad\Http\Utils\IpUtils;
use Psr\Http\Message\ServerRequestInterface;

/**
 * RequestMatcher compares a pre-defined set of checks against a Request instance.
 *
 * PSR-7 equivalent of Symfony's RequestMatcher
 *
 * Based on https://github.com/symfony/httpfoundation/blob/master/RequestMatcher.php by Fabien
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RequestMatcher implements RequestMatcherInterface
{
    /** @var null|string */
    private $path;

    /** @var null|string */
    private $host;

    /** @var null|int */
    private $port;

    /** @var string[] */
    private $methods = [];

    /** @var string[] */
    private $ips = [];

    /** @var array<string,string> */
    private $attributes = [];

    /** @var string[] */
    private $schemes = [];

    /**
     * @param string               $path
     * @param string               $host
     * @param string|string[]      $methods
     * @param string|string[]      $ips
     * @param string[]             $schemes
     * @param int                  $port
     * @param array<string,string> $attributes
     */
    public function __construct(
        string $path = null,
        string $host = null,
        $methods = null,
        $ips = null,
        array $schemes = null,
        int $port = null,
        array $attributes = []
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
        $ips = null !== $ips ? (array) $ips : [];

        $this->ips = \array_reduce($ips, static function (array $ips, string $ip) {
            return \array_merge($ips, \preg_split('/\s*,\s*/', $ip));
        }, []);
    }

    /**
     * Adds a check for the HTTP method.
     *
     * @param null|string|string[] $method An HTTP method or an array of HTTP methods
     */
    public function matchMethod($method): void
    {
        $this->methods = \array_map('strtoupper', (array) $method ?? []);
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
    public function matches(ServerRequestInterface $request): bool
    {
        $requestUri = $request->getUri();
        $pathInfo   = $this->resolveMatchPath($requestUri->getPath(), $request);

        if (!empty($this->schemes) && !\in_array($requestUri->getScheme(), $this->schemes, true)) {
            return false;
        }

        if (!empty($this->methods) && !\in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }

        foreach ($this->attributes as $key => $pattern) {
            if (!\preg_match('{' . $pattern . '}', $request->getAttribute($key))) {
                return false;
            }
        }

        if (null !== $this->path && !\preg_match('{' . $this->path . '}', \rawurldecode($pathInfo))) {
            return false;
        }

        if (null !== $this->host && !\preg_match('{' . $this->host . '}i', $requestUri->getHost())) {
            return false;
        }

        if (null !== $this->port && 0 < $this->port && $requestUri->getPort() !== $this->port) {
            return false;
        }

        if (IpUtils::checkIp($this->resolveMatchIps($request), $this->ips)) {
            return true;
        }

        // Note to future implementors: add additional checks above the
        // foreach above or else your check might not be run!
        return 0 === \count($this->ips);
    }

    private function resolveMatchPath(string $uri, ServerRequestInterface $request): string
    {
        $basePath = \dirname($request->getServerParams()['SCRIPT_NAME']);

        if (\strpos($uri, $basePath) !== false) {
            return \strlen($basePath) > 1 ? \substr($uri, \strlen($basePath)) ?? '/' : $uri;
        }

        return $uri;
    }

    private function resolveMatchIps(ServerRequestInterface $request): ?string
    {
        if ($request instanceof ServerRequest) {
            return $request->getRemoteAddress();
        }

        return $request->getServerParams()['REMOTE_ADDR'] ?? null;
    }
}
