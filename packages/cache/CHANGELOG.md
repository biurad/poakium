# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added
- Added tags implementation using **cache/doctrine-adapter**
- Added `BiuradPHP\Cache\Interfaces\CacheAdapterInterface` interface for **nette/di** support
- Added `BiuradPHP\Cache\AdapterFactory` class to replace `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Added phpunit tests for PSR6 and PRS-16

### Changed
- Uodated `BiuradPHP\Cache\Bridges\CaceExtension` class to adhere to changes made.
- Updated composer.json file
- Made changes to `README.md` file
- Updated `CHANGELOG.md` file for [unreleased] version
- Made changes to `CONTRIBUTING.md` file and moved it to **.github** folder

### Fixed
- Fixed minor issues with parsing integers when used with **nette/di**
- Fixed minor issues with undefined methods and class existence when used with **nette/di**
- Fixed minor issues with faling tests
- Fixed error of closure

### Removed
- Deleted `BiuradPHP\Cache\Bridges\DoctrineCachePass` class
- Deleted `.scrutinizer.yml` file
- Delete `BiuradPHP\Cache\Bridges\Connection` class as it's unused

[unreleased]: https://github.com/biurad/biurad-caching/compare/v0.2.4...master