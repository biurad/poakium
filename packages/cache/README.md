# The BiuradPHP Caching

[![Latest Version](https://img.shields.io/packagist/v/biurad/biurad-caching.svg?style=flat-square)](https://packagist.org/packages/biurad/biurad-caching)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/biurad/biurad-caching/1.5.svg?style=flat-square)](https://travis-ci.org/biurad/biurad-caching)
[![Coverage Status](https://codecov.io/gh/biurad/biurad-caching/graph/badge.svg?style=flat-square)](https://codecov.io/gh/biurad/biurad-caching)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/biurad-caching.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/biurad-caching)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://biurad.com/sponsor)

**biurad/biurad-caching** is a php cache library based on [Doctrine Cache][] created by [Divine Niiquaye][@divineniiquaye] which supports many different drivers such as redis, memcache, apc, mongodb and others. Implemented in [PSR-6] and [PSR-16] for great interoperability, performance and resiliency.

## 📦 Installation & Basic Usage

This project requires PHP 7.1 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/biurad-caching
```

This library is designed in an interoperable manner. Using [PSR-16] caching implementation, requires a [Doctrine Cache][] adapter, while [PSR-6] requires [PSR-16].

```php
// you can use any of doctrine cache adapter
$storage = new Biurad\Cache\AdapterFactory::createHandler('array');
// or
$storage = new Doctrine\Common\Cache\ArrayCache();
```

The `Doctrine\Common\Cache\Cache` storage is very simple for performance and in the first place, it provides full atomicity of operations.

| Strategy                      | Description                                                 |
| ----------------------------- | ----------------------------------------------------------- |
| BiuradPHP\Cache\SimpleCache   | For [PSR-16] caching abilities using doctrine cache adapter |
| BiuradPHP\Cache\CacheItemPool | For [PSR-6] caching abilities using [PSR-16]                |
| BiuradPHP\Cache\FastCache     | For advance and optimized [PSR-16]/[PSR-6] caching strategy |
| BiuradPHP\Cache\MemoryCache   | For caching using `var_export`                              |
| BiuradPHP\Cache\Preloader     | For PHP 7.4 opache.preload abilities                        |

Now you can create, retrieve, update and delete items using the above caching classes except `Preloader` class:

### For manipulation with cache using [PSR-16], we use the `Biurad\Cache\SimpleCache`:

---

If you want a quick caching strategy for your application, use [PSR-16] caching strategy. Its so simple and straight forward.

```php
use Biurad\Cache\SimpleCache;

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

### For manipulation with cache using [PSR-16], we use the `Biurad\Cache\CacheItemPool`:

---

If you want a bit advanced caching stratagy above [PSR-16], [PSR-6] is what you need, has a cool way of invalidating a missed cache.

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

### For manipulation with cache using `var_export`, we use the `BiuradPHP\Cache\MemoryCache`:

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

### For manipulation with cache using an advanced caching system, we use the `BiuradPHP\Cache\FastCache`:

---

For each method in `BiuradPHP\Cache\FastCache` class that has a second parameter as `callable`, which is called when there is no such item in the cache. This callback receives 2 arguments at the end by reference. The `Psr\Cache\CacheItemInterface` and a boolean, which you can use for setting expiration rules and saving data into cache.

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

The first argument of the `load()` method is a key, an arbitrary string that you associate to the cached value so you can retrieve it later. The second argument is a PHP callable which is executed when the key is not found in the cache to generate and return the value:

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

### For manipulation of PHP 7.4 opcache preload feature, we use the `BiuradPHP\Cache\Preloader`:

---

Starting from PHP 7.4, OPcache can compile and load classes at start-up and make them available to all requests until the server is restarted, improving performance significantly.

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

## 📓 Documentation

For in-depth documentation before using this library.. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com][docs].

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE].

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

Any classes or methods marked `@internal` are not intended for use outside of this library and are subject to breaking changes at any time, so please avoid using them.

## 🛠️ Maintenance & Support

When a new **major** version is released (`1.0`, `2.0`, etc), the previous one (`0.19.x`) will receive bug fixes for _at least_ 3 months and security updates for 6 months after that new release comes out.

(This policy may change in the future and exceptions may be made on a case-by-case basis.)

**Professional support, including notification of new releases and security updates, is available at [Biurad Commits][commit].**

## 👷‍♀️ Contributing

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

Contributions to this library are **welcome**, especially ones that:

- Improve usability or flexibility without compromising our ability to adhere to [PSR-6] and [PSR-16]
- Mirror fixes made to the [Doctrine Cache][]
- Optimize performance
- Fix issues with adhering to [PSR-6], [PSR-16] and [Doctrine Cache][]

Please see [CONTRIBUTING] for additional details.

## 🧪 Testing

```bash
$ composer test
```

This will tests biurad/biurad-caching will run against PHP 7.2 version or higher.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [Doctrine Team][]
- [All Contributors][]

This code is based on the [Doctrine Cache][] which is written, maintained and copyrighted by [Doctrine Team][]. This project simply wouldn't exist without their work.

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 📄 License

**biurad/biurad-caching** is licensed under the BSD-3 license. See the [`LICENSE`](LICENSE) file for more details.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

## 🗺️ Who Uses It?

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us an [email] or [message] mentioning this library. We publish all received request's at <https://patreons.biurad.com>.

Check out the other cool things people are doing with `biurad/biurad-caching`: <https://packagist.org/packages/biurad/biurad-caching/dependents>

<div align="center">
	<b>
		<a href="https://commits.biurad.com/pkg?utm_source=packagist-biurad-caching&utm_medium=referral&utm_campaign=readme">Get professional support for biurad/biurad-caching at Biurad Commits website</a>
	</b>
	<br>
	<sub>
		Biurad Commits is an aggregation of Biurad repositories, helping make our projects sustainable for maintainers.
	</sub>
</div>

[Composer]: https://getcomposer.org
[PSR-6]: http://www.php-fig.org/psr/psr-6/
[PSR-16]: http://www.php-fig.org/psr/psr-16/
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/biurad-caching
[commit]: https://commits.biurad.com/biurad-caching.git
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/biurad/biurad-caching/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
[Doctrine Cache]: https://github.com/doctrine/cache
[Doctrine Team]: https://www.doctrine-project.org
[Doctrine Documentation]: https://www.doctrine-project.org/projects/doctrine-cache/en/current/index.html
