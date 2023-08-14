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

namespace Biurad\Http;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\{IpUtils, Request};

/**
 * RequestMatcher compares a pre-defined set of checks against a Request instance.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RequestMatcher implements RequestMatcherInterface
{
    private ?string $path = null;
    private ?string $host = null;
    private ?int $port = null;

    /** @var string[] */
    private array $methods = [];

    /** @var string[] */
    private array $ips = [];

    /** @var string[] */
    private array $attributes = [];

    /** @var string[] */
    private array $schemes = [];

    /**
     * @param string|string[]|null $methods
     * @param string|string[]|null $ips
     * @param string|string[]|null $schemes
     */
    public function __construct(string $path = null, string $host = null, $methods = null, $ips = null, array $attributes = [], $schemes = null, int $port = null)
    {
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
     * @param string|string[]|null $scheme An HTTP scheme or an array of HTTP schemes
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
     * @param int|null $port The port number to connect to
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
     * @param string|string[]|null $ips A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     */
    public function matchIps($ips): void
    {
        $ips = null !== $ips ? (array) $ips : [];

        $this->ips = \array_reduce($ips, static fn (array $ips, string $ip) => \array_merge($ips, \preg_split('/\s*,\s*/', $ip)), []);
    }

    /**
     * Adds a check for the HTTP method.
     *
     * @param string|string[]|null $method An HTTP method or an array of HTTP methods
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

    public function matches(Request $request): bool
    {
        if ($this->schemes && !\in_array($request->getScheme(), $this->schemes, true)) {
            return false;
        }

        if ($this->methods && !\in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }

        foreach ($this->attributes as $key => $pattern) {
            $requestAttribute = $request->attributes->get($key);
            if (!\is_string($requestAttribute)) {
                return false;
            }
            if (!\preg_match('{'.$pattern.'}', $requestAttribute)) {
                return false;
            }
        }

        if (null !== $this->path && !\preg_match('{'.$this->path.'}', \rawurldecode($request->getPathInfo()))) {
            return false;
        }

        if (null !== $this->host && !\preg_match('{'.$this->host.'}i', $request->getHost())) {
            return false;
        }

        if (null !== $this->port && 0 < $this->port && $request->getPort() !== $this->port) {
            return false;
        }

        if (IpUtils::checkIp($request->getClientIp() ?? '', $this->ips)) {
            return true;
        }

        // Note to future implementors: add additional checks above the
        // foreach above or else your check might not be run!
        return 0 === \count($this->ips);
    }
}