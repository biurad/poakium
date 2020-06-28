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
use BadMethodCallException;
use BiuradPHP\Cache\CacheItem;
use BiuradPHP\Cache\CacheItemPool;
use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use BiuradPHP\Cache\SimpleCache;
use DateInterval;
use DateTime;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionProperty;

/**
 * @internal
 */
class Psr6CacheTest extends TestCase
{
    /** @var CacheItemPool */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $adapter        = new ArrayCache();
        $this->cache    = new CacheItemPool(new SimpleCache($adapter));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testProvider(): void
    {
        $pool = $this->cache;

        $this->assertInstanceOf(CacheItemPoolInterface::class, $pool);
        $key = 'pool';

        $this->assertTrue($pool->deleteItem($key));
        $this->assertFalse($pool->hasItem($key));

        $item = $pool->getItem($key);
        $item->set('bar');
        $this->assertTrue($pool->save($item));
        $this->assertTrue($pool->hasItem($key));
        $this->assertSame('bar', $pool->getItem($key)->get());

        $this->assertTrue($pool->deleteItem($key));
        $this->assertNull($pool->getItem($key)->get());

        $item = $pool->getItem($key);
        $item->set('bar');
        $pool->save($item);
        $this->assertTrue($pool->getItem($key)->isHit());

        $pool->clear();
        $this->assertNull($pool->getItem($key)->get());
        $this->assertFalse($pool->hasItem($key));
    }

    public function testInvalidKey(): void
    {
        $pool = $this->cache;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key "{}()/\@:" contains reserved characters "{}()/\@:');
        $pool->getItem(CacheItem::RESERVED_CHARACTERS);
    }

    public function testCacheItems(): void
    {
        $pool = $this->cache;

        $i0  = $pool->getItem('i0');
        $i1  = $pool->getItem('i1');
        $i2  = $pool->getItem('i2');
        $i3  = $pool->getItem('i3');
        $foo = $pool->getItem('foo');

        $pool->save($i0);
        $pool->save($i1);
        $pool->save($i2);
        $pool->save($i3);
        $pool->save($foo);

        $pool->deleteItems(['i0', 'i2']);

        $this->assertFalse($pool->getItem('i0')->isHit());
        $this->assertTrue($pool->getItem('i1')->isHit());
        $this->assertFalse($pool->getItem('i2')->isHit());
        $this->assertTrue($pool->getItem('i3')->isHit());
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool->deleteItems(['i1', 'i3']);

        $this->assertFalse($pool->getItem('i1')->isHit());
        $this->assertFalse($pool->getItem('i3')->isHit());
        $this->assertTrue($pool->getItem('foo')->isHit());

        $anotherPoolInstance = $this->cache;

        $this->assertFalse($anotherPoolInstance->getItem('i1')->isHit());
        $this->assertFalse($anotherPoolInstance->getItem('i3')->isHit());
        $this->assertTrue($anotherPoolInstance->getItem('foo')->isHit());
    }

    public function testInvalidateCommits(): void
    {
        $pool = $this->cache;

        $foo = $pool->getItem('foo');

        $pool->saveDeferred($foo->set('foo'));

        // ??: This seems to contradict a bit logic in deleteItems,
        // ??: where it does unset($this->deferred[$key]); on key matches

        $foo = $pool->getItem('foo');

        $this->assertTrue($foo->isHit());

        $pool->saveDeferred($foo);
        $this->assertTrue($pool->hasItem('foo'));
        $pool->clear();

        $item = $pool->getItem('foo');
        $item->set(function () {
            return 'value';
        });
        $pool->saveDeferred($item);

        $items = $pool->getItems(['foo', 'empty']);
        $items = \iterator_to_array($items);

        $key1 = $items['foo'];
        $this->assertIsCallable($key1->get());

        $key2 = $items['empty'];
        $this->assertFalse($key2->isHit());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testMultiples(): void
    {
        $data = [
            'foo'      => 'baz',
            'pool'     => 'bar',
        ];
        $pool = $this->cache;

        $this->assertTrue($pool->deleteItems(['foo', 'pool']));
        $this->assertFalse($pool->hasItem('foo'));
        $this->assertFalse($pool->hasItem('pool'));

        $item = $pool->getItem('foo');
        $item->set($data['foo']);
        $pool->save($item);

        $item = $pool->getItem('pool');
        $item->set($data['pool']);
        $pool->save($item);

        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->hasItem('pool'));

        $foundItems = $pool->getItems(\array_keys($data));
        $this->assertInstanceOf(Generator::class, $foundItems);

        $items = [];

        foreach (\iterator_to_array($foundItems) as $id => $item) {
            $this->assertTrue($item->isHit());
            $this->assertInstanceOf(CacheItemInterface::class, $item);
            $items[$id] = $item->get();
        }
        $this->assertSame($data, $items);

        $this->assertTrue($pool->deleteItems(\array_keys($data)));

        $foundItems = $pool->getItems(\array_keys($data));

        foreach (\iterator_to_array($foundItems) as $id => $item) {
            $this->assertNull($item->get());
        }

        $pool->clear();
    }

    public function testDefaultLifeTime(): void
    {
        $pool = $this->cache;

        $item = $pool->getItem('key.dlt');
        $r    = new ReflectionProperty($item, 'defaultLifetime');
        $r->setAccessible(true);
        $r->setValue($item, 2);

        $item->expiresAfter(null);
        $pool->save($item);
        $this->assertTrue($pool->getItem('key.dlt')->isHit());

        \sleep(3);

        $this->assertFalse($pool->getItem('key.dlt')->isHit());

        $item = $pool->getItem('foo');
        $r    = new ReflectionProperty($item, 'defaultLifetime');
        $r->setAccessible(true);
        $r->setValue($item, 2);

        $item->expiresAt(null);
        $pool->save($item);

        \sleep(1);

        $this->assertTrue($pool->getItem('foo')->isHit());

        \sleep(3);

        $this->assertFalse($pool->getItem('foo')->isHit());
    }

    public function testItemExpiry(): void
    {
        $pool = $this->cache;

        $item = $pool->getItem('foo');
        $item->expiresAfter(2);

        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        \sleep(3);

        $this->assertFalse($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo');
        $item->expiresAfter(DateInterval::createFromDateString('yesterday'));

        $pool->save($item);
        $this->assertFalse($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo');
        $item->expiresAt(new DateTime('2 second'));
        $pool->save($item);

        \sleep(1);

        $this->assertTrue($pool->getItem('foo')->isHit());

        \sleep(3);

        $this->assertFalse($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration date must be an integer, a DateInterval or null.');
        $item->expiresAfter('string');
    }

    public function testNotUnserializableAndDeferred(): void
    {
        $pool = new CacheItemPool(new SimpleCache(new PhpFileCache(__DIR__ . '/caches')));
        $pool->clear();

        $item = $pool->getItem('foo');
        $item->set(new Fixtures\NotUnserializableTest());
        $pool->save($item);
        $this->assertNull($pool->getItem('foo')->get());

        $pool->clear();

        $this->assertTrue($pool->deleteItems(['foo']));

        $item = $pool->getItem('foo');
        $item->set(new Fixtures\NotUnserializableTest());
        $pool->saveDeferred($item);

        $this->assertTrue($pool->deleteItem('foo'));

        $pool->clear();
    }

    public function testSerialization(): void
    {
        $this->expectException(BadMethodCallException::class);
        $pool = \serialize($this->cache);
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $pool);
        $this->assertInstanceOf(CacheItemPool::class, \unserialize($pool));
    }
}
