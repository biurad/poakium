# Cache manager using PSR-6, with easy-to-use API for quick caching

Cache accelerates your application by storing data - once hardly retrieved - for future use. The caching manager focuses on [Doctrine Cache](https://github.com/doctrine/cache) for caching. [Cache](https://github.com/cache/cache) will be supported in the future.

Cache manager utilizes PSR-16 and PSR-6 (psr7-middlewares) protocols to allow your application to communicate with cache engines.
For now let's work with [Doctrine Cache](https://github.com/doctrine/cache).

## Installation

The recommended way to install Cache Manager is via Composer:

```bash
composer require biurad/biurad-caching
```

It requires PHP version 7.0 and supports PHP up to 7.4. The dev-master version requires PHP 7.1.

## How To Use

Cache manager offers a very intuitive API for cache manipulation. Before we show you the first example, we need to think about place where
to store data physically. We can use a database, Memcached server, or the most available storage - hard drive. So we thought of using [Doctrine Cache](https://github.com/doctrine/cache) implementation:

```php
// the `temp` directory will be the storage
$storage = new Doctrine\Common\Cache\ArrayCache();
```

The `Doctrine\Common\Cache\Cache` storage is very well optimized for performance and in the first place,
it provides full atomicity of operations.

What does that mean? When we use cache we can be sure we are not reading a file that is not fully
written yet (by another thread) or that the file gets deleted "under our hands". Using the cache is therefore completely safe.

For manipulation with cache, we use the `BiuradPHP\Cache\SimpleCache`:

```php
use BiuradPHP\Cache\SimpleCache;
use BiuradPHP\Cache\Caching;

$psr = new SimpleCache($storage); // $storage from the previous example
$cache = new Caching($psr);
```

Let's save the contents of the '`$data`' variable under the '`$key`' key:

```php
$cache->save($key, $data);
```

This way, we can read from the cache: (if there is no such item in the cache, the `null` value is returned)

```php
$value = $cache->load($key);
if ($value === null) ...
```

Method `get()` has second parameter `callable` `$fallback`, which is called when there is no such item in the cache. This callback receives the array *$dependencies* by reference, which you can use for setting expiration rules.

```php
$cache->save($key, function(& $dependencies) {
  // some calculation
  
  return 15;
}));

$value = $cache->load($key);
```

We could delete the item from the cache either by saving null or by calling `delete()` method:

```php
$cache->save($key, null);
// or
$cache->remove($key);
```

It's possible to save any structure to the cache, not only strings. The same applies for keys.

Deleting the cache is a common operation when uploading a new application version to the server. At that moment, however, using [Doctrine Cache](https://github.com/doctrine/cache), the server can handle it's operations.
because it has to build a complete new cache. Retrieving some data is not difficult, cause [Doctrine Cache](https://github.com/doctrine/cache) create temporary memory data, so you don't run into further errors. If you are experiencing difficulties in caching.

The solution is to modify application behaviour so that data are created only by one thread and others are waiting. To do this, specify the value as a callback
or use an anonymous function:

```php
$result = $cache->save($key, function() {

	return buildData(); // difficult operation
});
```

The Cache-manager will ensure that the body of the function will be called only by one thread at once, and other threads will be waiting.
If the thread fails for some reason, another gets chance.

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

We publish all received request's on our website;

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
