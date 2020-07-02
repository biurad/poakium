# Cache manager using Doctrine Cache, offers a very intuitive PSR-16 and PSR-6 API for cache manipulation.

The Cache manager is a [Doctrine Cache](https://github.com/doctrine/cache) based system, providing features covering simple to advanced caching needs. Natively implements PSR-6 and PSR-16 for greatest interoperability. It is designed for performance and resiliency. It enables concurrent caching, cache stampede protection via locking early expiration and more advanced caching stragegies.

**`Please note that you can get the documentation for this dependency on [Doctrine website](https://www.doctrine-project.org/projects), doctrine-cache`**

## Installation

The recommended way to install Cache Manager is via Composer:

```bash
composer require biurad/biurad-caching
```

It requires PHP version 7.1 and supports PHP up to 7.4. The dev-master version requires PHP 7.2.

## How To Use

Cache manager offers a very intuitive API for cache manipulation. Before we show you the first example, we need to think about place where
to store data physically. We can use a database, Memcached server, or the most available storage - hard drive. So we thought of using [Doctrine Cache](https://github.com/doctrine/cache) implementation:

```php
// you can use any of doctrine cache adapter
$storage = new BiuradPHP\Cache\AdapterFactory::createHandler('array');
```

The `Doctrine\Common\Cache\Cache` storage is very well optimized for performance and in the first place, it provides full atomicity of operations.

What does that mean? When we use cache we can be sure we are not reading a file that is not fully written yet (by another thread) or that the file gets deleted "under our hands". Using the cache is therefore completely safe.

The package has 5 important strategies for caching, thus:

| Strategy                      | Description                                               |
| ----------------------------- | --------------------------------------------------------- |
| BiuradPHP\Cache\SimpleCache   | For PSR-16 caching abilities using doctrine cache adapter |
| BiuradPHP\Cache\CacheItemPool | For PSR-6 caching abilities                               |
| BiuradPHP\Cache\FastCache     | For advance and optimized PSR-16/PSR-6 caching strategy   |
| BiuradPHP\Cache\MemoryCache   | For caching using `var_export`                            |
| BiuradPHP\Cache\Preloader     | For php7.4 opache.preload abilities                       |

Now you can create, retrieve, update and delete items using the above caching classes except `Preloader` class:

> For manipulation with cache using psr-16, we use the `BiuradPHP\Cache\SimpleCache`:

---

```php
use BiuradPHP\Cache\SimpleCache;

$cache = new SimpleCache($storage); // psr-16 caching
```

```php
// assign a value to the item and save it
$cache->set('stats.products_count', 4711);

// retrieve the cache item
$productsCount = $cache->get('stats.products_count');

if (null === $productsCount) {
    // ... item does not exist in the cache
}

// retrieve the value stored by the item
$total = $productsCount;

// remove the cache item
$cache->delete('stats.products_count');
```

If you want a quick caching strategy for your application, use PSR-16 caching strategy. Its so simple and straight forward.

> For manipulation with cache using psr-16, we use the `BiuradPHP\Cache\CacheItemPool`:

---

```php
use BiuradPHP\Cache\CacheItemPool;

$cache = new CacheItemPool($cache); // psr-16 cache in psr-6 cache.
```

```php
// create a new item by trying to get it from the cache
$productsCount = $cache->getItem('stats.products_count');

// assign a value to the item and save it
$productsCount->set(4711);
$cache->save($productsCount);

// retrieve the cache item
$productsCount = $cache->getItem('stats.products_count');

if (!$productsCount->isHit()) {
    // ... item does not exist in the cache
}

// retrieve the value stored by the item
$total = $productsCount->get();

// remove the cache item
$cache->deleteItem('stats.products_count');
```

If you want a bit advanced caching stratagy above PSR-16, PSR-6 is what you need, has a cool way of invalidating a missed cache.

> For manipulation with cache using `var_export`, we use the `BiuradPHP\Cache\MemoryCache`:

---

```php
use BiuradPHP\Cache\MemoryCache;

$cache = new MemoryCache(getcwd() . '/memory_cache');
```

```php
// assign a value to the item and save it
$products = [...]; // An array of products
$cache->saveData('stats.products', $products);

if (null === $productsCount) {
    // ... item does not exist in the cache
}

// retrieve the value stored by the item
$total = $cache->loadData('stats.products');

// Remove cache item, by deleting the cache file.
```

> For manipulation with cache using advanced PSR-6, we use the `BiuradPHP\Cache\FastCache`:

---

For each method in `BiuradPHP\Cache\FastCache` class that has a second parameter `callable`, which is called when there is no such item in the cache. This callback receives 2 arguments at the end by reference. The `Psr\Cache\CacheItemInterface` and a boolean, which you can use for setting expiration rules and saving data into cache.

```php
use BiuradPHP\Cache\CacheItemPool;
use BiuradPHP\Cache\SimpleCache;
use BiuradPHP\Cache\FastCache;

// you can use any of doctrine cache adapter
$storage = new Doctrine\Common\Cache\ArrayCache();

$psr6 = new CacheItemPool($psr16 = new SimpleCache($storage)); // psr-16 cache in psr-6 cache.

$cache = new FastCache($psr16);
//or
$cache = new FastCache($psr6);
```

The first argument of the load() method is a key, an arbitrary string that you associate to the cached value so you can retrieve it later. The second argument is a PHP callable which is executed when the key is not found in the cache to generate and return the value:

```php
use Psr\Cache\CacheItemIterface;

// The callable will only be executed on a cache miss.
$value = $cache->load('my_cache_key', function (CacheItemInterface $item) {
    $item->expiresAfter(3600);

    // ... do some HTTP request or heavy computations
    $computedValue = 'foobar';

    return $computedValue;
});

echo $value; // 'foobar'

// ... and to remove the cache key
$cache->delete('my_cache_key');
```

The callable caching feature. Caching the result of a function or method call can be achieved using the `call()` method:

```php
$name = $cache->call('gethostbyaddr', $ip);
```

The `gethostbyaddr($ip)` will, therefore, be called only once and next time, only the value from cache will be returned. Of course, for different `$ip`, different results are cached. But if you want to set expiry time on call, add `Psr\Cache\CacheItemInterface` argument at the end and set the expiration time.

Similarly, it is possible to wrap a function with cache and call it later.

```php
function calculate($number)
{
	return 'number is ' . $number;
}

$wrapper = $cache->wrap('calculate');

$result = $wrapper(1); // number is 1
$result = $wrapper(2); // number is 2
```

The template/output caching feature. Caching the result of an output can be cached not only in templates:

```php
if ($block = $cache->start($key)) {
	... printing some data ...

	$block->end(); // save the output to the cache
}
```

In case that the output is already present in the cache, the `start()` method prints it and returns `null`. Otherwise, it starts to buffer the output and returns the `$block` object using which we finally save the data to the cache.

The expiration and invalidation caching feature.

This feature works with only PSR-6 cache, By default the beta is 1.0 and higher values mean earlier recompute. Set it to 0 to disable early recompute and set it to INF to force an immediate recompute:

```php
use Psr\Cache\CacheItemIterface;

$beta = 1.0;
$value = $cache->save('my_cache_key', function (CacheItemInterface $item) {
    $item->expiresAfter(3600);

    return '...';
}, $beta);
```

If you want more control over caching any php type except closures, this package is just for you. This package implements [Stampede prevention](https://en.wikipedia.org/wiki/Cache_stampede), concurrent caching and works perfectly with either PSR-6 or PSR-16 cache.

> For manipulation of php 7.4 opcache preload feature, we use the `BiuradPHP\Cache\Preloader`:

---

```php
use BiuradPHP\Cache\Preloader;

$preloadClasses = [...]; // A list array of classes to be appended for preloading.
$preloadFile = getcwd().'/opcache.preload.php'; // The file preloaded classes to fetch from.

Preloader::append($preloadFile, $preloadClasses);

// to check opcache preload statistics
var_dump(Preloader::getStatistics());
```

After the `$preloadFile` is written into, set the following configuration in your php.ini file:

```ini
; php.ini
opcache.preload=/path/to/opcache.preload.php

; maximum memory that OPcache can use to store compiled PHP files
opcache.memory_consumption=256

; maximum number of files that can be stored in the cache
opcache.max_accelerated_files=20000
```

Starting from PHP 7.4, OPcache can compile and load classes at start-up and make them available to all requests until the server is restarted, improving performance significantly.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server if any first in a separate terminal window.

With the server running, you can start testing.

```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please report using the issue tracker.
use our example [Issue Report](.github/ISSUE_TEMPLATE/Bug_report.md) template.

## Want to be listed on our projects website

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a message on our website, mentioning which of our package(s) you are using.

Post Here: [Project Patreons - https://patreons.biurad.com](https://patreons.biurad.com)

We publish all received request's on our website.

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/biurad-caching/contributers)

## Support us

`Biurad Lap` is a technology agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.
