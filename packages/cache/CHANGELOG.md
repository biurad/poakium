# Changelog

All notable changes to `biurad/biurad-caching` will be documented in this file.

## 0.1.10 - 2020-03-28
- Added doctrine cache adapter *Memcached*

## 0.1.9 - 2020-02-27

- Added deprecated doctrine cache adapter *Memcache*, (maybe removed again)
- Fixed return of *SimpleCache::getMultiple* to iterable.
- Changed `EventInterface` to *EventDispatcherInterface*
- Changes in *CacheBrdige* and *CacheExtension* classes
- Added *Caching* class for advanced caching, implemented from nette/caching
- Added *OutputHelper* class as helper for `Caching` class

## 0.1.7 - 2019-12-17

- Removed *CacheResolver* class
- Removed *"mongodb"* cache driver, since it wouldn't be used
- Added *CacheBridge* class for better performance working with BiuradPHP Framework
- Added *Connection* class for easy connection to servers
- Fixed Security issues regarding caching.
- Updated RREADME.md file

## 0.1.5 - 2019-11-21

- Fixed issues with phpunit test
- Updated ReadMe.md file
- Renamed Test name to *DoctrineCacheTest*
- Renamed Cache class to *SimpleCache*
- Added testing method *testMultiples*

## 0.1.0 - 2019-11-17

- First release
