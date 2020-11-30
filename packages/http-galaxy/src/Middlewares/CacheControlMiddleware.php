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

use ArrayObject;
use Biurad\Http\Cache\CacheControl;
use Biurad\Http\Cache\Generator\SimpleGenerator;
use Biurad\Http\Interfaces\CacheKeyGeneratorInterface;
use Biurad\Http\Interfaces\CacheListenerInterface;
use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Exception\TransferException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;

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

    /** @var ArrayObject */
    private $config;

    /**
     * Cache directives indicating if a response can not be cached.
     *
     * @var string[]
     */
    private $noCacheFlags = ['no-cache', 'private', 'no-store'];

    /**
     * @param CacheItemPoolInterface $pool
     * @param StreamFactoryInterface $streamFactory
     * @param array<string,mixed>    $config        {
     *
     *     @var int $default_ttl (seconds) If we do not respect cache headers or can't calculate a good ttl, use this
     *              value
     *     @var string $hash_algo The hashing algorithm to use when generating cache keys
     *     @var int $cache_lifetime (seconds) To support serving a previous stale response when the server answers 304
     *              we have to store the cache for a longer time than the server originally says it is valid for.
     *              We store a cache item for $cache_lifetime + max age of the response.
     *     @var array $methods list of request methods which can be cached
     *     @var array $blacklisted_paths list of regex for URLs explicitly not to be cached
     *     @var array $respect_response_cache_directives list of cache directives this plugin will respect while caching responses
     *     @var CacheKeyGeneratorInterface $cache_key_generator an object to generate the cache key. Defaults to a new instance of SimpleGenerator
     *     @var CacheListenerInterface[] $cache_listeners an array of objects to act on the response based on the results of the cache check.
     *              Defaults to an empty array
     * }
     */
    public function __construct(CacheItemPoolInterface $pool, StreamFactoryInterface $streamFactory, array $config = [])
    {
        if (!$streamFactory instanceof StreamFactoryInterface) {
            throw new TypeError(\sprintf('Argument 2 passed to %s::__construct() must be of type %s, %s given.', self::class, StreamFactoryInterface::class, \is_object($streamFactory) ? \get_class($streamFactory) : \gettype($streamFactory)));
        }

        $this->pool          = $pool;
        $this->streamFactory = $streamFactory;
        $this->config        = $this->configureOptions($config);

        if (null === $this->config['cache_key_generator']) {
            $this->config['cache_key_generator'] = new SimpleGenerator();
        }
    }

    /**
     * This method will setup the CacheControlMiddleware in client cache mode.
     * When using the client cache mode the middleware will
     * cache responses with `private` cache directive.
     *
     * @param CacheItemPoolInterface $pool
     * @param StreamFactoryInterface $streamFactory
     * @param array                  $config        For all possible config options see the constructor docs
     *
     * @return CacheControlMiddleware
     */
    public static function clientCache(CacheItemPoolInterface $pool, $streamFactory, array $config = [])
    {
        // Allow caching of private requests
        if (isset($config['respect_response_cache_directives'])) {
            $config['respect_response_cache_directives'][] = 'no-cache';
            $config['respect_response_cache_directives'][] = 'max-age';
            $config['respect_response_cache_directives']   = \array_unique($config['respect_response_cache_directives']);
        } else {
            $config['respect_response_cache_directives'] = ['no-cache', 'max-age'];
        }

        return new self($pool, $streamFactory, $config);
    }

    /**
     * This method will setup the CacheControlMiddleware in server cache mode. This is the default caching behavior it refuses to
     * cache responses with the `private`or `no-cache` directives.
     *
     * @param CacheItemPoolInterface $pool
     * @param StreamFactoryInterface $streamFactory
     * @param array<string,mixed>    $config        For all possible config options see the constructor docs
     *
     * @return CacheControlMiddleare
     */
    public static function serverCache(CacheItemPoolInterface $pool, $streamFactory, array $config = [])
    {
        return new self($pool, $streamFactory, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = \strtoupper($request->getMethod());

        // if the request not is cachable, move to $next
        if (!\in_array($method, $this->config['methods'])) {
            return $this->handleCacheListeners($request, $handler->handle($request), false, null);
        }

        // If we can cache the request
        $key       = $this->createCacheKey($request);
        $cacheItem = $this->pool->getItem($key);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            // The array_key_exists() is to be removed in 1.0.
            if (\array_key_exists('expiresAt', $data) && (null === $data['expiresAt'] || \time() < $data['expiresAt'])) {
                // This item is still valid according to previous cache headers
                $response = $this->createResponseFromCacheItem($cacheItem);

                return $this->handleCacheListeners($request, $response, true, $cacheItem);
            }

            // Add headers to ask the server if this cache is still valid
            if ($modifiedSinceValue = $this->getModifiedSinceHeaderValue($cacheItem)) {
                $request = $request->withHeader('If-Modified-Since', $modifiedSinceValue);
            }

            if ($etag = $this->getETag($cacheItem)) {
                $request = $request->withHeader('If-None-Match', $etag);
            }
        }

        return $this->createResponse($handler->handle($request), $request, $cacheItem);
    }

    /**
     * Verify that we can cache this response.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isCacheable(ResponseInterface $response)
    {
        if (!\in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 410])) {
            return false;
        }

        $nocacheDirectives = \array_intersect($this->config['respect_response_cache_directives'], $this->noCacheFlags);

        foreach ($nocacheDirectives as $nocacheDirective) {
            if (CacheControl::getCacheControlDirective($response, $nocacheDirective)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new response into cache.
     */
    private function createResponse(ResponseInterface $response, RequestInterface $request, CacheItemInterface $cacheItem)
    {
        if (304 === $response->getStatusCode()) {
            if (!$cacheItem->isHit()) {
                /*
                 * We do not have the item in cache. This plugin did not add If-Modified-Since
                 * or If-None-Match headers. Return the response from server.
                 */
                return $this->handleCacheListeners($request, $response, false, $cacheItem);
            }

            // The cached response we have is still valid
            $data              = $cacheItem->get();
            $maxAge            = $this->getMaxAge($response);
            $data['expiresAt'] = $this->calculateResponseExpiresAt($maxAge);
            $cacheItem->set($data)->expiresAfter($this->calculateCacheItemExpiresAfter($maxAge));
            $this->pool->save($cacheItem);

            return $this->handleCacheListeners($request, $this->createResponseFromCacheItem($cacheItem), true, $cacheItem);
        }

        if ($this->isCacheable($response) && $this->isCacheableRequest($request)) {
            $bodyStream = $response->getBody();
            $body       = $bodyStream->__toString();

            if ($bodyStream->isSeekable()) {
                $bodyStream->rewind();
            } else {
                $response = $response->withBody($this->streamFactory->createStream($body));
            }

            $maxAge = $this->getMaxAge($response);
            $cacheItem
                ->expiresAfter($this->calculateCacheItemExpiresAfter($maxAge))
                ->set([
                    'response'  => $response,
                    'body'      => $body,
                    'expiresAt' => $this->calculateResponseExpiresAt($maxAge),
                    'createdAt' => \time(),
                    'etag'      => $response->getHeader('ETag'),
                ]);
            $this->pool->save($cacheItem);
        }

        return $this->handleCacheListeners($request, $response, false, $cacheItem ?? null);
    }

    /**
     * Calculate the timestamp when this cache item should be dropped from the cache. The lowest value that can be
     * returned is $maxAge.
     *
     * @param null|int $maxAge
     *
     * @return null|int Unix system time passed to the PSR-6 cache
     */
    private function calculateCacheItemExpiresAfter($maxAge)
    {
        if (null === $this->config['cache_lifetime'] && null === $maxAge) {
            return;
        }

        return $this->config['cache_lifetime'] + $maxAge;
    }

    /**
     * Calculate the timestamp when a response expires. After that timestamp, we need to send a
     * If-Modified-Since / If-None-Match request to validate the response.
     *
     * @param null|int $maxAge
     *
     * @return null|int Unix system time. A null value means that the response expires when the cache item expires
     */
    private function calculateResponseExpiresAt($maxAge)
    {
        if (null === $maxAge) {
            return null;
        }

        return \time() + $maxAge;
    }

    /**
     * Verify that we can cache this request.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function isCacheableRequest(RequestInterface $request)
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
     * @param RequestInterface $request
     *
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
     * @param ResponseInterface $response
     *
     * @return null|int
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
            return (new DateTime($header))->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return $this->config['default_ttl'];
    }

    /**
     * Configure default options for cache control.
     *
     * @param array<string,mixed> $options
     *
     * @return ArrayObject
     */
    private function configureOptions(array $options): ArrayObject
    {
        return new ArrayObject(
            \array_replace([
                'cache_lifetime'                    => 86400 * 30, // 30 days
                'default_ttl'                       => 0,
                'hash_algo'                         => 'sha1',
                'methods'                           => ['GET', 'HEAD'],
                'respect_response_cache_directives' => ['no-cache', 'private', 'max-age', 'no-store'],
                'cache_key_generator'               => null,
                'cache_listeners'                   => [],
                'blacklisted_paths'                 => [],
            ], $options)
        );
    }

    /**
     * @param CacheItemInterface $cacheItem
     *
     * @return ResponseInterface
     */
    private function createResponseFromCacheItem(CacheItemInterface $cacheItem): ResponseInterface
    {
        $data = $cacheItem->get();

        /** @var ResponseInterface $response */
        $response = $data['response'];
        $stream   = $this->streamFactory->createStream($data['body']);

        try {
            $stream->rewind();
        } catch (Exception $e) {
            throw new TransferException('Cannot rewind stream.', 0, $e);
        }

        $response = $response->withBody($stream);

        return $response;
    }

    /**
     * Get the value of the "If-Modified-Since" header.
     *
     * @param CacheItemInterface $cacheItem
     *
     * @return null|string
     */
    private function getModifiedSinceHeaderValue(CacheItemInterface $cacheItem)
    {
        $data = $cacheItem->get();
        // The isset() is to be removed in 1.0.
        if (!isset($data['createdAt'])) {
            return;
        }

        $modified = new DateTime('@' . $data['createdAt']);
        $modified->setTimezone(new DateTimeZone('GMT'));

        return \sprintf('%s GMT', $modified->format('l, d-M-y H:i:s'));
    }

    /**
     * Get the ETag from the cached response.
     *
     * @param CacheItemInterface $cacheItem
     *
     * @return null|string
     */
    private function getETag(CacheItemInterface $cacheItem)
    {
        $data = $cacheItem->get();
        // The isset() is to be removed in 1.0.
        if (!isset($data['etag'])) {
            return;
        }

        foreach ($data['etag'] as $etag) {
            if (!empty($etag)) {
                return $etag;
            }
        }
    }

    /**
     * Call the cache listeners, if they are set.
     *
     * @param RequestInterface        $request
     * @param ResponseInterface       $response
     * @param bool                    $cacheHit
     * @param null|CacheItemInterface $cacheItem
     *
     * @return ResponseInterface
     */
    private function handleCacheListeners(RequestInterface $request, ResponseInterface $response, $cacheHit, $cacheItem)
    {
        foreach ($this->config['cache_listeners'] as $cacheListener) {
            $response = $cacheListener->onCacheResponse($request, $response, $cacheHit, $cacheItem);
        }

        return $response;
    }
}
