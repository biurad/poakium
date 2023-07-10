<div align="center">

# The Poakium Cache

[![Latest Version](https://img.shields.io/packagist/v/biurad/cache?include_prereleases&label=Latest&style=flat-square)](https://packagist.org/packages/biurad/cache)
[![Workflow Status](https://img.shields.io/github/actions/workflow/status/biurad/poakium/ci.yml?branch=master&label=Workflow&style=flat-square)](https://github.com/biurad/poakium/actions?query=workflow)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?&label=Poakium&style=flat-square)](LICENSE)
[![Maintenance Status](https://img.shields.io/maintenance/yes/2023?label=Maintained&style=flat-square)](https://github.com/biurad/poakium)

</div>

---

A [PHP][1] library that provides a high-performance caching system for storing the results of expensive computations, database queries, or network requests. Supports both [PSR-6][2] and [PSR-16][3] caching standards.

## 📦 Installation

This project requires [PHP][1] 7.2 or higher. The recommended way to install, is via [Composer][4]. Simply run:

```bash
$ composer require biurad/cache
```

## 📍 Quick Start

One of the key features of this library is its ability to cache the result of a callable or function, allowing for the caching of complex computations or database queries. Additionally, it includes a fallback load feature to ensure that the cached value is always fetched, even if it has expired from the cache.

Here is an example of how to use the library using [symfony/cache][5]:

```php
use Biurad\Cache\FastCache as Cache;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

$storage = new PhpFilesAdapter(directory: __DIR__.'/cache');
$cache = new Cache($storage);
$cache->setCacheItem(\Symfony\Component\Cache\CacheItem::class);

// The callable will only be executed on a cache miss.
$value = $cache->load(
    'my_cache_key',
    static function (CacheItemInterface $item): CacheItemInterface {
        $item->expiresAfter(3600);

        // ... do some HTTP request or heavy computations
        $item->set('foobar');

        return $item;
    }
);

echo $value; // 'foobar'

// ... and to remove the cache key
$cache->delete('my_cache_key');

// cache the result of the function
$ip = $cache->call('gethostbyaddr', "127.0.0.1");

function calculate(array $a, array $b): array {
    $result = [];
    foreach ($a as $i => $v) foreach ($v as $k => $m) $result[$i][$k] = $m + $b[$i][$k];

    return $result;
}

$matrix = $cache->wrap('calculate');
$result = $matrix([[1, 2, 3], [4, 5, 6]], [[7, 8, 9], [10, 11, 12]]) // [[8, 10, 12], [14, 16, 18]]

// Caching the result of printable contents using PHP echo.
if ($block = $cache->start($key)) {
	  ... printing some data ...

	  $block->end(); // save the output to the cache
}
```

> **NB:** The beta parameter found on cache methods default value is 1.0 and higher values mean earlier recompute. Set it to 0 to disable early recompute and set it to INF to force an immediate recompute:

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][6]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][7] to support future development.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][8] is the author this library.
- [All Contributors][9] who contributed to this project.

## 📄 License

Poakium Cache is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://php.net
[2]: http://www.php-fig.org/psr/psr-6/
[3]: http://www.php-fig.org/psr/psr-16/
[4]: https://getcomposer.org
[5]: https://github.com/symfony/cache
[6]: https://docs.biurad.com/poakium/cache
[7]: https://biurad.com/sponsor
[8]: https://github.com/divineniiquaye
[9]: https://github.com/biurad/php-cache/contributors
