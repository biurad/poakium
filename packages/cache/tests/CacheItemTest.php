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

use BiuradPHP\Cache\CacheItem;
use BiuradPHP\Cache\Exceptions\InvalidArgumentException;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class CacheItemTest extends TestCase
{
    public function testValidKey(): void
    {
        $this->assertSame('foo', CacheItem::validateKey('foo'));
    }

    /**
     * @dataProvider provideInvalidKey
     */
    public function testInvalidKey($key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key');
        CacheItem::validateKey($key);
    }

    public function provideInvalidKey(): array
    {
        return [
            [''],
            ['{'],
            ['}'],
            ['('],
            [')'],
            ['/'],
            ['\\'],
            ['@'],
            [':'],
            [true],
            [null],
            [1],
            [1.1],
            [[[]]],
            [new Exception('foo')],
        ];
    }

    public function testItem(): void
    {
        $item = new CacheItem();
        $r    = new ReflectionProperty($item, 'key');
        $r->setAccessible(true);
        $r->setValue($item, 'foo');

        $r = new ReflectionProperty($item, 'defaultLifetime');
        $r->setAccessible(true);
        $r->setValue($item, 1);

        $item->set('data');

        $this->assertEquals('foo', $item->getKey());
        $this->assertEquals('data', $item->get());

        $item->expiresAt(new DateTime());
        $r = new ReflectionProperty(CacheItem::class, 'expiry');
        $r->setAccessible(true);
        $this->assertIsFloat($r->getValue($item));

        $item->expiresAfter(null);
        $r = new ReflectionProperty($item, 'expiry');
        $r->setAccessible(true);
        $this->assertIsFloat($r->getValue($item));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration date must implement DateTimeInterface or be null.');
        $item->expiresAt('string');
    }
}
