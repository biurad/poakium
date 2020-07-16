# Change Log

All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [0.2.4] - 2020-06-22
### Changed
- Updated **composer.json** file
- Updated php files header doc since version [0.2.3]

## [0.2.3] - 2020-06-19
### Added
- Added `BiuradPHP\Cache\Preloader` class for PHP 7.4 opcache.preload feature
- Added support for PSR-6 implementation
- Added needed classes that support PSR-6 implementation

### Changed
- Updated **composer.json** file
- Made changes to `CHANGELOG.md` file
- Made changes to most classes to support new caching feature

### Fixed
- Fixed minor bugs and unnoticed errors in caching
- Reduced code complexity and adjusted performance

## [0.2.2] - 2020-06-03
### Changed
- Removed all imported functions in codebase
- Updated **composer.json**

### Fixed
- Fixed issue with undefined index error in `BiuradPHP\Cache\FastCache` class

## [0.2.1] - 2020-05-17
### Changed
- Updated **composer.json** file
- Added `Doctrine\Common\Cache\PhpFileCache` adapter as `memory` in `BiuradPHP\Cache\Bridges\DoctrineCachePass`
- Renamed `BiuradPHP\Cache\Caching` class to `BiuradPHP\Cache\FastCache` to avoid class naming conflict
- Renamed `BiuradPHP\Cache\Bridges\CachePass` class to `BiuradPHP\Cache\Bridges\DoctrineCachePass`
- Renamed `BiuradPHP\Cache\Memory` class to `BiuradPHP\Cache\MemoryCache`
- Made changes to `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Made changes to `BiuradPHP\Cache\OutputHelper` class constructor
- Updated README.md file

### Fixed
- Fixed issues with `Doctrine\Common\Cache\MemcachedCache` connection in the `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Removed unused non-static protected method `BiuradPHP\Cache\Bridges\Connection::createDatabase`

### Removed
- Deleted unused `BiuradPHP\Cache\Handles\DatabaseCache` class

## [0.2.0] - 2020-04-29
### Added
- Added `Doctrine\Common\Cache\MemcachedCache` adapter for usage with nette/di
- Added static method `BiuradPHP\Cache\Bridges\Connection::createMemcached`
- Added `BiuradPHP\Cache\Memory` class with a support interface for `var_export` feature

### Changed
- Made changes to `CHANGELOG.md` file
- Made changes to `README.md` file
- Made changes to phpunit.xml.dist file
- Set **driver** config usage in `BiuradPHP\Cache\Bridges\CcheExtension` (nette/di) to be required
- Renamed `BiuradPHP\Cache\Bridges\CacheBridge` to `BiuradPHP\Cache\Bridges\CachePass`
- Updated **composer.json** file

### Fixed
- Fixed found documentation and type-hint bugs (methods, variables, classes)
- Fixed minor issues in `BiuradPHP\Cache\Bridges\CacheExtension` class
- Fixed major breaks and issues in `BiuradPHP\Cache\Bridges\CachePass` class

### Removed
- Deleted Deprecated `BiuradPHP\Cache\Bridges\CacheResolver` class

## [0.1.9] - 2020-02-27
### Added
- Added `BiuradPHP\Cache\Bridges\Connection` class to resolve doctrine cache adapter's connection for nette/di
- Added deprecated `Doctrine\Common\Cache\MemcacheCache` adapter for usage with **nette/di**, (maybe removed in future)
- Added `BiuradPHP\Cache\Bridges\CacheExtension` class
- Added `BiuradPHP\Cache\Caching` class. a reference implementation of [**nette/caching**](https://github.com/nette/caching)
- Added `BiuradPHP\Cache\Exceptions\CacheException` class
- Added `BiuradPHP\Cache\OutputHelper` as a helper for `BiuradPHP\Cache\Caching` class

### Changed
- Made changes to `README.md` file
- Made changes to `CHANGELOG.md` file
- Updated **composer.json** file
- Changed `BiuradPHP\Event\Interfaces\EventInterface` to `BiuradPHP\Events\Interfaces\EventDispatcherInterface` in `BiuradPHP\Cache\SimpleCache`
- Updated `BiuradPHP\Cache\Handlers\DatabaseCache`
- Updated `BiuradPHP\Cache\Bridges\CacheExtension` class to support `BiuradPHP\Cache\Bridges\CacheBridge`
- Mark `BiuradPHP\Cache\Bridges\CacheResolver` class as deprecated

### Fixed
- Fixed minor issues with `BiuradPHP\Cache\Bridges\CacheBridge` class
- Fixed minor issues with `BiuradPHP\Cache\Bridges\Connection` class
- Fixed minor issues with `BiuradPHP\Cache\SimpleCache` class
- Fixed major issues with `BiuradPHP\Cache\Handlers\DatabaseCache` class

## [0.1.5] - 2019-11-24
### Added
- Added issue templates to .github folder
- Added a `LICENSE` file
- Added `CHANGELOG.md` file to track changes
- Added `CONTRIBUTING.md` file for contributes
- Added a few tests using phpunit

### Changed
 - Made changes to `README.md` file
 - Updated **composer.json** file
 - Updated `BiuradPHP\Cache\Bridges\CacheResolver` to adhere with recent changes
 - Renamed `BiuradPHP\Cache\Cache` class to `BiuradPHP\Cache\SimpleCache`
 - Renamed previous test class name to `BiuradPHP\Cache\Tests\DoctrineCacheTest`

### Fixed
- Renamed **.scrutinizer** to **.scrutinizer.yml**
- Renamed **.travis** to **.travis.yml**
- Fixed minor issues with failing phpunit tests

## [0.1.3] - 2019-11-17
### Changed
 - Updated **composer.json** file

## [0.1.0 - 0.1.1] - 2019-11-17
### Added
 - Initial commit (support PSR-16 implementation)
 - Added .travis test file
 - Added .scrutinizer test file
 - Added .gitignore file

[0.2.4]: https://github.com/biurad/biurad-caching/compare/v0.2.3...v0.2.4
[0.2.3]: https://github.com/biurad/biurad-caching/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/biurad/biurad-caching/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/biurad/biurad-caching/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/biurad/biurad-caching/compare/v0.1.9...v0.2.0
[0.1.9]: https://github.com/biurad/biurad-caching/compare/v0.1.5...v0.1.9
[0.1.5]: https://github.com/biurad/biurad-caching/compare/v0.1.3...v0.1.5
[0.1.3]: https://github.com/biurad/biurad-caching/compare/v0.1.1...v0.1.3
[0.1.0 - 0.1.1]: https://github.com/biurad/biurad-caching/compare/v0.1.0...v0.1.1
