<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

use Biurad\Cache\CacheItem;
use Biurad\Cache\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    public function testGetKey(): void
    {
        $item = new CacheItem();

        $r = new \ReflectionProperty($item, 'key');
        $r->setAccessible(true);
        $r->setValue($item, 'test_key');

        self::assertEquals('test_key', $item->getKey());
    }

    public function testSet(): void
    {
        $item = new CacheItem();
        self::assertNull($item->get());

        $item->set('data');
        self::assertEquals('data', $item->get());
    }

    public function testGet(): void
    {
        $item = new CacheItem();
        self::assertNull($item->get());

        $item->set('data');
        self::assertEquals('data', $item->get());
    }

    public function testHit(): void
    {
        $item = new CacheItem();
        self::assertFalse($item->isHit());

        $r = new \ReflectionProperty($item, 'isHit');
        $r->setAccessible(true);
        $r->setValue($item, true);

        self::assertTrue($item->isHit());
    }

    public function testGetExpirationTimestamp(): void
    {
        $item = new CacheItem();

        $r = new \ReflectionProperty($item, 'expiry');
        $r->setAccessible(true);

        self::assertNull($r->getValue($item));

        $timestamp = \time();

        $r->setValue($item, $timestamp);
        self::assertEquals($timestamp, $r->getValue($item));
    }

    public function testExpiresAt(): void
    {
        $item = new CacheItem();

        $r = new \ReflectionProperty($item, 'expiry');
        $r->setAccessible(true);

        $item->expiresAt(new \DateTime('30 seconds'));
        self::assertEquals(30, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

        $item->expiresAt(null);
        self::assertNull($r->getValue($item));
    }

    public function testExpiresAtException(): void
    {
        $item = new CacheItem();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration date must implement DateTimeInterface or be null.');

        $item->expiresAt('string');
    }

    public function testExpiresAfter(): void
    {
        $item = new CacheItem();
        $timestamp = \time() + 1;

        $r = new \ReflectionProperty($item, 'expiry');
        $r->setAccessible(true);

        $item->expiresAfter($timestamp);
        self::assertEquals($timestamp, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

        $item->expiresAfter(new \DateInterval('PT1S'));
        self::assertEquals(1, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

        $item->expiresAfter(null);
        self::assertNull($r->getValue($item));
    }
}
