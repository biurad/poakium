# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added
- Added tags implementation using **cache/doctrine-adapter**
- Added `BiuradPHP\Cache\Interfaces\CacheAdapterInterface` interface for **nette/di** support
- Added `BiuradPHP\Cache\AdapterFactory` class to replace `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Added changelog for 0.x versions
- Added Dependabot to make changes to **composer.json** file
- Added **.editorconfig** and **.gitattributes** files
- Added phpunit tests for PSR6 and PRS-16
- Added tests for **psalm**, **github actions**, **phpstan** and **phpcs** for PSR-12 coding standing
- Added **cache/simple-cache-bridge** and **cache/integration-tests** integration

### Changed
- Replaced `BiuradPHP` namespace to `Biurad` on all classes.
- Updated `BiuradPHP\Cache\Bridges\CacheExtension` class to adhere to changes made.
- Added `branch-alias` to **composer.json** extra config field.
- Abandoned **biurad/biurad-caching** use **biurad/cache** on composer `require` command instead
- Updated **composer.json** file
- Made changes to `README.md` file
- Updated `CHANGELOG.md` file for [unreleased] version
- Replaced `Support_us.md` with `FUNDING.yml` in **.github** folder
- Made changes to `CONTRIBUTING.md` file and moved it to **.github** folder
- Made `Biurad\Cache\SimleCache` class as bridge to PSR-6
- Updated `BiuradPHP\Cache\Bridges\CacheExtension` class namespace to add a `Nette` before `CacheExtension`

### Fixed
- Fixed minor issues with parsing integers when used with **nette/di**
- Fixed minor issues with undefined methods and class existence when used with **nette/di**
- Fixed minor issues with falling tests
- Renamed **Tests** folder to lowercase
- Fixed error of closure

### Removed
- Deleted `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Deleted `.scrutinizer.yml` file
- Delete `BiuradPHP\Cache\Bridges\Connection` class as it's unused
- Deleted `Biurad\Cache\Preloader` class, use darkghosthunter/preloader library instead
- Removed memory cache implementation for var_export
- Removed classes under `Biurad\Cache\Bridges` sub-namspace and folder

[unreleased]: https://github.com/biurad/biurad-caching/compare/v0.2.4...master
