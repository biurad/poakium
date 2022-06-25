# The Biurad PHP Events Bus

[![Latest Version](https://img.shields.io/packagist/v/biurad/events-bus.svg?style=flat-square)](https://packagist.org/packages/biurad/events-bus)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-events-bus/Tests?style=flat-square)](https://github.com/biurad/php-events-bus/actions?query=workflow%3ATests)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-events-bus?style=flat-square)](https://codeclimate.com/github/biurad/php-events-bus)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-events-bus?style=flat-square)](https://codecov.io/gh/biurad/php-events-bus)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-events-bus.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-events-bus)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://biurad.com/sponsor)

**biurad/php-events-bus** is an events dispatcher for [PHP] 7.2+ based on [The Symfony EventDispatcher][symfony-event-dispatcher], created by [Fabien Potencier][@fabpot]. which provides tools that allow your application components to communicate with each other by dispatching events and listening to them.

[The Symfony EventDispatcher][symfony-event-dispatcher] component implements the [Mediator](https://en.wikipedia.org/wiki/Mediator_pattern) and [Observer](https://en.wikipedia.org/wiki/Observer_pattern) design patterns to make all these things possible and to make your projects truly extensible. This package adds a touch of [PSR-11] container, removes [The Symfony Stopwatch][symfony-stopwatch] component from traceable events, and adds a minor performance gain.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.2 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/events-bus
```

The dispatcher is the central object of the event dispatcher system. In general, a single dispatcher is created, which maintains a registry of listeners. When an event is dispatched via the dispatcher, it notifies all listeners registered with that event. In addition to registering listeners with existing events, you can create and dispatch your own events. This is useful feature by symfony to keep different components of your own system flexible and decoupled:

```php
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();
```

The lazy event dispatcher can be used to [PSR-11] container autowiring for listeners called on events, you can add [The DivineNii PHP Invoker][divinenii-php-invoker] library's class instance `DivineNii\Invoker\Invoker`, injecting optional callable resolvers and a [PSR-11] container complaint instance.

```php
use Biurad\Events\LazyEventDispatcher;

$dispatcher = new LazyEventDispatcher();
```

To take advantage of an existing event, you need to connect a listener to the dispatcher so that it can be notified when the event is dispatched. A call to the dispatcher's addListener() method associates any valid PHP callable to an event:

```php
use Symfony\Contracts\EventDispatcher\Event;

$listener = new AcmeListener();

$dispatcher->addListener('acme.foo.action', [$listener, 'onFooAction']);

// or

$dispatcher->addListener('acme.foo.action', function (Event $event) {
    // will be executed when the acme.foo.action event is dispatched
});
```

Once a listener is registered with the dispatcher, it waits until the event is notified. In the above example, when the `acme.foo.action` event is dispatched, the dispatcher calls the given listener method or closure and passes the [Event](http://api.symfony.com/master/Symfony/Contracts/EventDispatcher/Event.html) object as the single argument.

The Traceable EventDispatcher is an event dispatcher that wraps any other event dispatcher and can then be used to determine which event listeners have been called by the dispatcher. Pass the event dispatcher to be wrapped and an instance of the [PSR-3] logger to its constructor:

```php
use Biurad\Events\TraceableEventDispatcher;
use Psr\Log\NullLogger;

// the event dispatcher to debug
$dispatcher = ...;

$traceableEventDispatcher = new TraceableEventDispatcher($dispatcher, new NullLogger());
```
After your application has been processed, you can use the [getCalledListeners()](http://api.symfony.com/master/Symfony/Component/EventDispatcher/Debug/TraceableEventDispatcher.html#method_getCalledListeners) method to retrieve an array of event listeners that have been called in your application. Similarly, the [getNotCalledListeners()](http://api.symfony.com/master/Symfony/Component/EventDispatcher/Debug/TraceableEventDispatcher.html#method_getNotCalledListeners) method returns an array of event listeners that have not been called:

```php
// ...

$calledListeners = $traceableEventDispatcher->getCalledListeners();
$notCalledListeners = $traceableEventDispatcher->getNotCalledListeners();
```

## 📓 Documentation

To have more of documentation, I advice you check out [The Symfony EventDispatcher][symfony-event-dispatcher] component documentation provided on [Symfony](https://symfony.com) website.

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

- Improve usability or flexibility without compromising our ability to adhere to [Mediator](https://en.wikipedia.org/wiki/Mediator_pattern) and [Observer](https://en.wikipedia.org/wiki/Observer_pattern) design patterns.
- Optimize performance
- Fix issues with adhering to this package.

Please see [CONTRIBUTING] for additional details.

## 🧪 Testing

```bash
$ composer test
```

This will tests biurad/events-bus will run against PHP 7.2 version or higher.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 📄 License

**biurad/php-events-bus** is licensed under the BSD-3 license. See the [`LICENSE`](LICENSE) file for more details.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

## 🗺️ Who Uses It?

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us an [email] or [message] mentioning this library. We publish all received request's at <https://patreons.biurad.com>.

Check out the other cool things people are doing with `biurad/php-events-bus`: <https://packagist.org/packages/biurad/events-bus/dependents>

[PHP]: https://php.net
[Composer]: https://getcomposer.org
[@divineniiquaye]: https://github.com/divineniiquaye
[commit]: https://commits.biurad.com/php-events-bus.git
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/biurad/php-events-bus/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
[PSR-3]: http://www.php-fig.org/psr/psr-3/
[PSR-11]: http://www.php-fig.org/psr/psr-11/
[divinenii-php-invoker]: https://github.com/divineniiquaye/php-invoker
[@fabpot]: https://github.com/fabpot
[symfony-event-dispatcher]: https://github.com/symfony/event-dispatcher
[symfony-stopwatch]: https://github.com/symfony/stopwatch
