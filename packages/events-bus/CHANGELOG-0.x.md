# Change Log

All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [0.1.6] - 2020-06-24
### Added
- Added ability to resolve subscribers with PSR-11 container

## [0.1.5] - 2020-06-17
### Changed
- Updated php files header doc
- Updated `CHANGELOG.md` file
- updated **composer.json** file
- Updated methods and classes doc headers

## [0.1.3] - 2020-05-20
### Added
- Added typehint for "strict_types" on most classes

### Fixed
- Fixed event subscribers issues with nette/di: `BiuradPHP\Events\Bridges\EventsExtension` class

## [0.1.2] - 2020-05-20
### Added
- Added Serializable support to `BiuradPHP\Events\EventDispatcher` class

### Changed
- Updated **travis.yml** test file to remove tests for PHP 7.1
- Updated **composer.json** file

### Fixed
- Fixed minor and major issues with events classes

### Removed
- Deleted `BiuradPHP\Events\EventsContext` class
- Deleted `BiuradPHP\Events\Interfaces\EventBroadcastInterface`

## [0.1.1] - 2020-05-04
### Added
- Added `BiuradPHP\Events\Bridges\EventsExtension::beforeCompile` method

#### Changed
- Updated `CONTRIBUTING.md` file
- Updated `BiuradPHP\Events\Interfaces\EventDispatcherInterface` to support below PHP 7.2

## [0.1.0] - 2020-05-01
### Added
- Initial commit

[0.1.6]: https://github.com/biurad/php-events-bus/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/biurad/php-events-bus/compare/v0.1.3...v0.1.5
[0.1.3]: https://github.com/biurad/php-events-bus/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/biurad/php-events-bus/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/biurad/php-events-bus/compare/v0.1.0...v0.1.1
