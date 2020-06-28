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

namespace BiuradPHP\Cache\Tests;

use __PHP_Incomplete_Class;
use ArrayIterator;
use BadMethodCallException;
use BiuradPHP\Cache\CacheItem;
use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use BiuradPHP\Cache\SimpleCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * @internal
 */
class Psr16CacheTest extends TestCase
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

        $this->assertInstanceOf(CacheInterface::class, $cache);

        $key = CacheItem::RESERVED_CHARACTERS;

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->has($key));

        $this->assertTrue($cache->set($key, 'bar'));
        $this->assertTrue($cache->has($key));
        $this->assertSame('bar', $cache->get($key));

        $this->assertTrue($cache->delete($key));
        $this->assertNull($cache->get($key));
        $this->assertTrue($cache->set($key, 'bar'));

        $cache->clear();
        $this->assertNull($cache->get($key));
        $this->assertFalse($cache->has($key));
    }

    public function testNullProviderMultiples(): void
    {
        $data = [
            'foo'                          => 'baz',
            CacheItem::RESERVED_CHARACTERS => 'bar',
        ];
        $cache = new SimpleCache(new Fixtures\NullAdapterTest());

        $this->assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', 'empty', CacheItem::RESERVED_CHARACTERS])
        ));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        $this->assertTrue($cache->setMultiple(new ArrayIterator($data)));
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->has(CacheItem::RESERVED_CHARACTERS));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        $this->assertInstanceOf(Traversable::class, $foundMultiple);
        $this->assertSame($data, \iterator_to_array($foundMultiple));

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

        $this->assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS])
        ));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        $this->assertTrue($cache->setMultiple(new ArrayIterator($data)));
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->has(CacheItem::RESERVED_CHARACTERS));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        $this->assertInstanceOf(Generator::class, $foundMultiple);
        $this->assertSame($data, $foundMultiple->getReturn());

        $this->assertTrue($cache->deleteMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS])));

        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        $this->assertEmpty($foundMultiple->getReturn());

        $this->assertTrue($cache->setMultiple(new ArrayIterator($data)));

        $cache->clear();
        $foundMultiple = $cache->getMultiple(new ArrayIterator(['foo', CacheItem::RESERVED_CHARACTERS]));
        $this->assertEmpty($foundMultiple->getReturn());

        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has(CacheItem::RESERVED_CHARACTERS));

        $this->expectException(InvalidArgumentException::class);
        $cache->getMultiple(null)->getReturn();
    }

    public function testNotUnserializable(): void
    {
        $cache = new SimpleCache(new FilesystemCache(__DIR__ . '/caches'));
        $cache->clear();

        $cache->set('foo', new Fixtures\NotUnserializableTest());

        $this->expectException(Exception::class);
        $this->assertNull($cache->get('foo'));
    }

    public function testSerialization(): void
    {
        $this->expectException(BadMethodCallException::class);
        $cache = \serialize($this->cache);
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $cache);
        $this->assertInstanceOf(SimpleCache::class, $cache = \unserialize($cache));
    }
}
