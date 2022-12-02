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

namespace Biurad\Http\Middlewares;

use Biurad\Http\Cache\CacheControl;
use Biurad\Http\Cache\Generator\SimpleGenerator;
use Biurad\Http\Exception\InvalidArgumentException;
use Biurad\Http\Interfaces\CacheListenerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Allow for caching a response with a PSR-6 compatible caching engine.
 *
 * It can follow the RFC-7234 caching specification or use a fixed cache lifetime.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheControlMiddleware implements MiddlewareInterface
{
    /** @var CacheItemPoolInterface */
    private $pool;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var array<string,mixed> */
    private $config;

    /**
     * Cache directives indicating if a response can not be cached.
     *
     * @var string[]
     */
    private $noCacheFlags = ['no-cache', 'private', 'no-store'];

    /**
     * @param array<string,mixed> $config
     *  - default_ttl: (seconds) If we do not respect cache headers or can't calculate a good ttl, use this value
     *  - hash_algo: The hashing algorithm to use when generating cache keys
     *  - cache_lifetime: (seconds) To support serving a previous stale response when the server answers 304
     *    we have to store the cache for a longer time than the server originally says it is valid for.
     *    We store a cache item for cache_lifetime + max age of the response.
     *  - methods: list of request methods which can be cached
     *  - blacklisted_paths: list of regex for URLs explicitly not to be cached
     *  - respect_response_cache_directives: list of cache directives this plugin will respect while caching responses
     *  - cache_key_generator: an object to generate the cache key. Defaults to a new instance of SimpleGenerator
     *  - cache_listeners: an array of objects to act on the response based on the results of the cache check. Defaults to an empty array
     */
    public function __construct(CacheItemPoolInterface $pool, StreamFactoryInterface $streamFactory, array $config = [])
    {
        $this->pool = $pool;
        $this->streamFactory = $streamFactory;
        $this->config = $config += [
            'cache_lifetime' => 86400 * 30, // 30 days
            'default_ttl' => 0,
            'hash_algo' => 'sha256',
            'methods' => ['GET', 'HEAD'],
            'respect_response_cache_directives' => ['no-cache', 'private', 'max-age'],
            'cache_key_generator' => new SimpleGenerator(),
            'cache_listeners' => [],
            'blacklisted_paths' => [],
        ];
    }

    /**
     * This method will setup the CacheControlMiddleware in client cache mode.
     * When using the client cache mode the middleware will
     * cache responses with `private` cache directive.
     *
     * @param StreamFactoryInterface $streamFactory
     * @param array                  $config        For all possible config options see the constructor docs
     *
     * @return static
     */
    public static function clientCache(CacheItemPoolInterface $pool, $streamFactory, array $config = [])
    {
        // Allow caching of private requests
        if (isset($config['respect_response_cache_directives'])) {
            $config['respect_response_cache_directives'][] = 'no-cache';
            $config['respect_response_cache_directives'][] = 'max-age';
            $config['respect_response_cache_directives'] = \array_unique($config['respect_response_cache_directives']);
        } else {
            $config['respect_response_cache_directives'] = ['no-cache', 'max-age'];
        }

        return new static($pool, $streamFactory, $config);
    }

    /**
     * This method will setup the CacheControlMiddleware in server cache mode. This is the default caching behavior it refuses to
     * cache responses with the `private`or `no-cache` directives.
     *
     * @param StreamFactoryInterface $streamFactory
     * @param array<string,mixed>    $config        For all possible config options see the constructor docs
     *
     * @return static
     */
    public static function serverCache(CacheItemPoolInterface $pool, $streamFactory, array $config = [])
    {
        return new static($pool, $streamFactory, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = \strtoupper($request->getMethod());

        // if the request not is cacheable, move to $handler
        if (!\in_array($method, $this->config['methods'], true)) {
            return $this->handleCacheListeners($request, $handler->handle($request), null);
        }

        // If we can cache the request
        $cacheItem = $this->pool->getItem($this->createCacheKey($request));

        if (!empty($data = $cacheItem->get())) {
            if (empty($data['expiresAt']) || \time() < $data['expiresAt']) {
                // This item is still valid according to previous cache headers
                $response = $this->createResponseFromCacheItem($data['response'], $data['body'], $cacheItem->getKey());

                return $this->handleCacheListeners($request, $response, $cacheItem);
            }

            // Add headers to ask the server if this cache is still valid
            if ($modifiedSinceValue = $this->getModifiedSinceHeaderValue('@' . $data['createdAt'])) {
                $request = $request->withHeader('If-Modified-Since', $modifiedSinceValue);
            }

            if ($etag = $this->getETag($data['etag'] ?? [])) {
                $request = $request->withHeader('If-None-Match', $etag);
            }
        }

        return $this->createResponse($handler->handle($request), $request, $cacheItem);
    }

    /**
     * Verify that we can cache this response.
     */
    protected function isCacheable(ResponseInterface $response): bool
    {
        if (!\in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 410])) {
            return false;
        }

        $nonCacheDirectives = \array_intersect($this->config['respect_response_cache_directives'], $this->noCacheFlags);

        foreach ($nonCacheDirectives as $nonCacheDirective) {
            if (CacheControl::getCacheControlDirective($response, $nonCacheDirective)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new response into cache.
     *
     * @return ResponseInterface
     */
    private function createResponse(ResponseInterface $response, RequestInterface $request, CacheItemInterface $cacheItem)
    {
        if (304 === $response->getStatusCode()) {
            // The cached response we have is still valid
            if (!empty($data = $cacheItem->get())) {
                $data['expiresAt'] = $this->calculateResponseExpiresAt($maxAge = $this->getMaxAge($response));
                $this->pool->save($cacheItem->set($data)->expiresAfter($this->calculateCacheItemExpiresAfter($maxAge)));
                $response = $this->createResponseFromCacheItem($data['response'], $data['body'], $cacheItem->getKey());
            }

            return $this->handleCacheListeners($request, $response, $cacheItem);
        }

        if ($this->isCacheable($response) && $this->isCacheableRequest($request)) {
            $bodyStream = $response->getBody();
            $body = $bodyStream->__toString();

            if ($bodyStream->isSeekable()) {
                $bodyStream->rewind();
            } else {
                $response = $response->withBody($this->streamFactory->createStream($body));
            }

            $cacheItem
                ->expiresAfter($this->calculateCacheItemExpiresAfter($maxAge = $this->getMaxAge($response)))
                ->set([
                    'response' => $response,
                    'body' => $body,
                    'expiresAt' => $this->calculateResponseExpiresAt($maxAge),
                    'createdAt' => \time(),
                    'etag' => $response->getHeader('ETag'),
                ]);
            $this->pool->save($cacheItem);
        }

        return $this->handleCacheListeners($request, $response, $cacheItem ?? null);
    }

    /**
     * Calculate the timestamp when this cache item should be dropped from the cache. The lowest value that can be
     * returned is $maxAge.
     *
     * @param int|null $maxAge
     *
     * @return int|null Unix system time passed to the PSR-6 cache
     */
    private function calculateCacheItemExpiresAfter($maxAge): ?int
    {
        if (null === $this->config['cache_lifetime'] && null === $maxAge) {
            return null;
        }

        return $this->config['cache_lifetime'] + $maxAge;
    }

    /**
     * Calculate the timestamp when a response expires. After that timestamp, we need to send a
     * If-Modified-Since / If-None-Match request to validate the response.
     *
     * @param int|null $maxAge
     *
     * @return int|null Unix system time. A null value means that the response expires when the cache item expires
     */
    private function calculateResponseExpiresAt($maxAge): ?int
    {
        if (empty($maxAge)) {
            return null;
        }

        return \time() + $maxAge;
    }

    /**
     * Verify that we can cache this request.
     */
    private function isCacheableRequest(RequestInterface $request): bool
    {
        $uri = $request->getUri()->__toString();

        foreach ($this->config['blacklisted_paths'] as $regex) {
            if (1 === \preg_match($regex, $uri)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    private function createCacheKey(RequestInterface $request)
    {
        $key = $this->config['cache_key_generator']->generate($request);

        return \hash($this->config['hash_algo'], $key);
    }

    /**
     * Get a ttl in seconds. It could return null if we do not respect cache headers and got no defaultTtl.
     *
     * @return int|null
     */
    private function getMaxAge(ResponseInterface $response)
    {
        if (!\in_array('max-age', $this->config['respect_response_cache_directives'], true)) {
            return $this->config['default_ttl'];
        }

        // check for max age in the Cache-Control header
        $maxAge = CacheControl::getCacheControlDirective($response, 'max-age');

        if (!\is_bool($maxAge)) {
            $ageHeaders = $response->getHeader('Age');

            foreach ($ageHeaders as $age) {
                return $maxAge - ((int) $age);
            }

            return (int) $maxAge;
        }

        // check for ttl in the Expires header
        $headers = $response->getHeader('Expires');

        foreach ($headers as $header) {
            return (new \DateTime($header))->getTimestamp() - (new \DateTime())->getTimestamp();
        }

        return $this->config['default_ttl'];
    }

    /**
     * @param ResponseInterface|mixed $response
     */
    private function createResponseFromCacheItem($response, string $body, string $cacheKey): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            $response->getBody()->write($body);

            return $response;
        }

        $this->pool->deleteItem($cacheKey);

        throw new InvalidArgumentException('Could not read response from cache id: ' . $cacheKey);
    }

    /**
     * Get the value of the "If-Modified-Since" header.
     */
    private function getModifiedSinceHeaderValue(string $createdAt): string
    {
        $modified = new \DateTime($createdAt);
        $modified->setTimezone(new \DateTimeZone('GMT'));

        return $modified->format('l, d-M-y H:i:s') . ' GMT';
    }

    /**
     * Get the ETag from the cached response.
     *
     * @param array<int,string> $eTags
     */
    private function getETag(array $eTags): ?string
    {
        foreach ($eTags as $etag) {
            if (!empty($etag)) {
                return $etag;
            }
        }

        return null;
    }

    /**
     * Call the cache listeners, if they are set.
     *
     * @return ResponseInterface
     */
    private function handleCacheListeners(RequestInterface $request, ResponseInterface $response, ?CacheItemInterface $cacheItem)
    {
        foreach ($this->config['cache_listeners'] as $cacheListener) {
            /** @var CacheListenerInterface $cacheListener */
            $cacheListener = \is_string($cacheListener) ? new $cacheListener() : $cacheListener;

            $response = $cacheListener->onCacheResponse($request, $response, $cacheItem);
        }

        return $response;
    }
}
