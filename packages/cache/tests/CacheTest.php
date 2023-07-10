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

namespace Biurad\Cache\Tests;

use Biurad\Cache\CacheItem;
use Biurad\Cache\FastCache as Cache;
use PHPUnit\Framework as t;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

function vec_dot_product(): int
{
    $a = \range(150000, 250000);
    $b = 0;

    foreach (\range(1, 100000) as $i => $v) {
        $b += $v * $a[$i];
    }

    return $b;
}

function addition_matrix(array $a, array $b): array
{
    $result = [];

    foreach ($a as $i => $v) {
        foreach ($v as $k => $m) {
            $result[$i][$k] = $m + $b[$i][$k];
        }
    }

    return $result;
}

dataset('adapters', [
    'PSR-6' => [new ArrayAdapter()],
    'PSR-16' => [new Psr16Cache(new ArrayAdapter())],
]);

test('cache with get key from cache item', function (): void {
    $item = new CacheItem();
    $r = new \ReflectionProperty($item, 'key');
    $r->setAccessible(true);
    $r->setValue($item, 'test_key');

    t\assertEquals('test_key', $item->getKey());
});

test('cache with set value into cache item', function (): void {
    $item = new CacheItem();
    t\assertNull($item->get());

    $item->set('data');
    t\assertEquals('data', $item->get());
});

test('cache with get value from cache item', function (): void {
    $item = new CacheItem();
    t\assertNull($item->get());

    $item->set('data');
    t\assertEquals('data', $item->get());
});

test('cache with hit checking for cache item', function (): void {
    $item = new CacheItem();
    t\assertFalse($item->isHit());

    $r = new \ReflectionProperty($item, 'isHit');
    $r->setAccessible(true);
    $r->setValue($item, true);

    t\assertTrue($item->isHit());
});

test('cache with expiration timestamp on cache item', function (): void {
    $item = new CacheItem();

    $r = new \ReflectionProperty($item, 'expiry');
    $r->setAccessible(true);
    t\assertNull($r->getValue($item));

    $r->setValue($item, $timestamp = \time());
    t\assertEquals($timestamp, $r->getValue($item));
});

test('cache with expires at timestamp on cache item', function (): void {
    $item = new CacheItem();

    $r = new \ReflectionProperty($item, 'expiry');
    $r->setAccessible(true);

    $item->expiresAt(new \DateTime('30 seconds'));
    t\assertEquals(30, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

    $item->expiresAt(null);
    t\assertNull($r->getValue($item));
});

test('cache with expires after timestamp for cache item', function (): void {
    $item = new CacheItem();
    $timestamp = \time() + 1;

    $r = new \ReflectionProperty($item, 'expiry');
    $r->setAccessible(true);

    $item->expiresAfter($timestamp);
    t\assertEquals($timestamp, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

    $item->expiresAfter(new \DateInterval('PT1S'));
    t\assertEquals(1, (int) (0.1 + $r->getValue($item) - (float) \microtime(true)));

    $item->expiresAfter(null);
    t\assertNull($r->getValue($item));
});

test('cache with storage into cache', function (): void {
    $storage = new ArrayAdapter();
    $cache = new Cache($storage);
    $cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

    t\assertSame($storage, $cache->getStorage());
    t\assertEmpty($cache->getNamespace());
});

test('cache with load() method ->', function ($storage): void {
    $cache = new Cache($storage);
    $cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

    $value = $cache->load('hello', static function (CacheItemInterface $item) {
        $item->expiresAfter(1);
        $item->set('foobar');

        return $item;
    });
    t\assertEquals($value, $cache->load('hello'));

    \sleep(1);
    t\assertNull($cache->load('hello'));
})->with('adapters');

test('cache with bulkLoad() method ->', function ($storage): void {
    $cache = new Cache($storage);
    $cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

    $values = $cache->bulkLoad(['a', 'b', 'c'], static function (string $key, CacheItemInterface $item): CacheItemInterface {
        if ('b' === $key) {
            $item->expiresAfter(1);

            return $item->set('foobar');
        }

        return $item->set('default');
    });
    t\assertEquals($values, $cache->bulkLoad(['a', 'b', 'c']));

    \sleep(1);
    t\assertEquals(['a' => 'default', 'b' => null, 'c' => 'default'], $cache->bulkLoad(['a', 'b', 'c']));
})->with('adapters');

test('cache with save, load, and delete', function ($storage): void {
    $cache = new Cache($storage);
    $cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

    $cache->save('foo', $fn = function (CacheItemInterface $item): CacheItemInterface {
        $item->set('HelloWorld');

        return $item;
    });
    t\assertSame('HelloWorld', $cache->load('foo'));
    $cache->delete('foo');
    t\assertNull($cache->load('foo'));

    $cache->save('foo', $fn, -2.0);
})->with('adapters')->throws(
    \InvalidArgumentException::class,
    'Argument "$beta" provided to "Biurad\Cache\FastCache::save()" must be a positive number, -2.000000 given.'
);

test('cache with callable values and namespaced keys', function ($storage): void {
    $cache = new Cache($storage, 'fn<>');
    $cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

    t\assertEquals('fn<>', $cache->getNamespace());
    t\assertIsCallable('Biurad\Cache\Tests\vec_dot_product');
    t\assertEquals(1083340833300000, $a = $cache->call('Biurad\Cache\Tests\vec_dot_product'));
    t\assertEquals($a, $cache->call('Biurad\Cache\Tests\vec_dot_product'));

    $cal = $cache->wrap('Biurad\Cache\Tests\addition_matrix');
    t\assertEquals(
        $a = [[8, 10, 12], [14, 16, 18]],
        $cal([[1, 2, 3], [4, 5, 6]], [[7, 8, 9], [10, 11, 12]]),
    );
    t\assertEquals(
        $a,
        $cal([[1, 2, 3], [4, 5, 6]], [[7, 8, 9], [10, 11, 12]]),
    );
    t\assertEquals(
        $b = [[5, 5, 10, 9], [6, 12, 7, 13], [14, 16, 16, 13], [11, 6, 3, 13]],
        $cal(
            [[2, 4, 6, 8], [1, 3, 5, 7], [9, 8, 7, 6], [2, 3, 1, 5]],
            [[3, 1, 4, 1], [5, 9, 2, 6], [5, 8, 9, 7], [9, 3, 2, 8]]
        ),
    );
    t\assertEquals(
        $b,
        $cal(
            [[2, 4, 6, 8], [1, 3, 5, 7], [9, 8, 7, 6], [2, 3, 1, 5]],
            [[3, 1, 4, 1], [5, 9, 2, 6], [5, 8, 9, 7], [9, 3, 2, 8]]
        ),
    );
})->with('adapters');
