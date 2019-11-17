<?php

namespace BiuradPHP\Cache\Tests;

use BiuradPHP\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class DoctrineCacheTest extends TestCase
{
    public function testProvider()
    {
        $pool = new ArrayCache();
        $cache = new Cache($pool);

        $this->assertInstanceOf(CacheProvider::class, $cache);

        $key = '{}()/\@:';

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->has($key));

        $this->assertTrue($cache->set($key, 'bar'));
        $this->assertTrue($cache->has($key));
        $this->assertSame('bar', $cache->get($key));

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->get($key));
        $this->assertTrue($cache->set($key, 'bar'));

        $cache->flushAll();
        $this->assertFalse($cache->get($key));
        $this->assertFalse($cache->has($key));
    }
}
