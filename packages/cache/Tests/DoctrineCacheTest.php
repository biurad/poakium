<?php

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  CacheManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/cachemanager
 * @since     Version 0.1.3
 */

namespace BiuradPHP\Cache\Tests;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use BiuradPHP\Cache\SimpleCache;
use Psr\SimpleCache\CacheInterface;
use Doctrine\Common\Cache\ArrayCache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class DoctrineCacheTest extends TestCase
{
    /** @var SimpleCache */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $pool = new ArrayCache();
        $this->cache = new SimpleCache($pool);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testSimpleProvider(): void
    {
        $cache = $this->cache;

        $this->assertInstanceOf(CacheInterface::class, $cache);

        $key = '{}()/\@:';

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

    /**
     * @throws InvalidArgumentException
     */
    public function testMultiples(): void
    {
        $data = [
            'foo' => 'baz',
            '{}()/\@:' => 'bar',
        ];
        $cache = $this->cache;

        $this->assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', '{}()/\@:'])
        ));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('{}()/\@:'));

        $this->assertTrue($cache->setMultiple(new ArrayIterator($data)));
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->has('{}()/\@:'));
        $this->assertSame($data, $cache->getMultiple(
            new ArrayIterator(['foo', '{}()/\@:'])
        ));

        $this->assertTrue($cache->deleteMultiple(
            new ArrayIterator(['foo', '{}()/\@:'])
        ));
        $this->assertEmpty($cache->getMultiple(
            new ArrayIterator(['foo', '{}()/\@:'])
        ));
        $this->assertTrue($cache->setMultiple(new ArrayIterator($data)));

        $cache->clear();
        $this->assertEmpty($cache->getMultiple(
            new ArrayIterator(['foo', '{}()/\@:'])
        ));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('{}()/\@:'));
    }
}
