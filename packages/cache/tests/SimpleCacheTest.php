<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache\Tests;

use __PHP_Incomplete_Class;
use ArrayIterator;
use BadMethodCallException;
use Biurad\Cache\CacheItem;
use Biurad\Cache\SimpleCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Traversable;

/**
 * @internal
 */
class SimpleCacheTest extends TestCase
{
    /** @var SimpleCache */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $pool        = new ArrayCache();
        $this->cache = new SimpleCache($pool);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testProvider(): void
    {
        $cache = $this->cache;

        self::assertInstanceOf(CacheInterface::class, $cache);

        $key = CacheItem::RESERVED_CHARACTERS;

        self::assertTrue($cache->delete($key));
        self::assertFalse($cache->has($key));

        self::assertTrue($cache->set($key, 'bar'));
        self::assertTrue($cache->has($key));
        self::assertSame('bar', $cache->get($key));

        self::assertTrue($cache->delete($key));
        self::assertNull($cache->get($key));
        self::assertTrue($cache->set($key, 'bar'));

        $cache->clear();
        self::assertNull($cache->get($key));
        self::assertFalse($cache->has($key));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testNullProviderMultiples(): void
    {
        $data = [
            'foo'                          => 'baz',
            CacheItem::RESERVED_CHARACTERS => 'bar',
        ];
        $cache = new SimpleCache(new Fixtures\NullAdapterTest());

        self::assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', 'empty', CacheItem::RESERVED_CHARACTERS])
        ));
        self::assertFalse($cache->has('foo'));
        self::assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        self::assertTrue($cache->setMultiple(new ArrayIterator($data)));
        self::assertTrue($cache->has('foo'));
        self::assertTrue($cache->has(CacheItem::RESERVED_CHARACTERS));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        self::assertInstanceOf(Traversable::class, $foundMultiple);
        self::assertSame($data, \iterator_to_array($foundMultiple));

        $cache->clear();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testMultiples(): void
    {
        $data = [
            'foo'                          => 'baz',
            CacheItem::RESERVED_CHARACTERS => 'bar',
        ];
        $cache = $this->cache;

        self::assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS])
        ));
        self::assertFalse($cache->has('foo'));
        self::assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        self::assertTrue($cache->setMultiple(new ArrayIterator($data)));
        self::assertTrue($cache->has('foo'));
        self::assertTrue($cache->has(CacheItem::RESERVED_CHARACTERS));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        self::assertInstanceOf(Generator::class, $foundMultiple);
        self::assertSame($data, $foundMultiple->getReturn());

        self::assertTrue($cache->deleteMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS])));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        self::assertEmpty($foundMultiple->getReturn());

        self::assertTrue($cache->setMultiple(new ArrayIterator($data)));

        $cache->clear();
        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        self::assertEmpty($foundMultiple->getReturn());

        self::assertFalse($cache->has('foo'));
        self::assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        $this->expectException(InvalidArgumentException::class);
        $cache->getMultiple(null)->getReturn();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testNotUnserializable(): void
    {
        $cache = new SimpleCache(new FilesystemCache(__DIR__ . '/caches'));
        $cache->clear();

        $cache->set('foo', new Fixtures\NotUnserializableTest());

        $this->expectException(Exception::class);
        self::assertNull($cache->get('foo'));
    }

    public function testSerialization(): void
    {
        $this->expectException(BadMethodCallException::class);
        $cache = \serialize($this->cache);
        self::assertInstanceOf(__PHP_Incomplete_Class::class, $cache);
        self::assertInstanceOf(SimpleCache::class, $cache = \unserialize($cache));
    }
}
