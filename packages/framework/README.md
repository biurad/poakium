# Application Skeleton for [PHP-Rade] 👊

This is a skeleton application for creating applications using [PHP-Rade]. It is pre-configured, clean and easy to use. If you interested, you might check out our demo applications:

* [Distributed Chat App][] - This application implements an simple and real-time messaging system in PHP.
* [Symfony Like Blog App][] - This application is a simple and a [symfony's demo][sf-demo] like application.
* [Distributed Blog App][] - This application implements an advanced viral media blogging system in PHP.

> :rocket: `master` is automatically deployed to [radephp.ml](https://radephp.ml)

## 🔰 Introduction

This project is structured using [Separation of Concerns][SOC] principle, instead of strictly following [MVC] design pattern. Providing modern and rapid development, with the flexibility to customized and use with any php library out there.

## 🔥 Features

- Flexible URI routing.
- Code reusable and easier to maintain.
- High-performance Lightweight PHP framework
- Perfect Container management, Dependency Injection (DI)
- PSR-{2,3,4,6,7,11,15,16,17} compliant
- Integration with external libraries
- Shipped with Tracy exceptions handler and debugger
- Flexible configuration's setup, highly scalable
- Supports hybrid runtime: [RoadRunner], [ReactPHP], [AmPHP], or [Swoole]
- And MORE ...


## 📦 Getting Started & Installation

For getting started with this project or contributing, you have to follow the below procedure. First navigate to a main directory. Then run below command on terminal for getting started.

```sh
git clone https://github.com/biurad/php-framework.git
cd php-framework

# Default packages in composer.lock maybe not be compatible with your PHP version.
composer update
```

OR

```sh
composer create-project biurad/framework my_project
```

Once installed, you can test it out immediately using PHP's built-in web server:

```sh
$ php rade serve
# OR use php command
$ php -S 127.0.0.1:8000 -t public
# OR use the composer alias:
$ composer run --timeout 0 serve
```

To update the project directory's structure to the latest version, run the following command:

```sh
php rade self-update
```

## 📓 Documentation

For in-depth documentation before using this library. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com](https://docs.biurad.com).

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE].

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

## 👷‍♀️ Contributing

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

Contributions to this library are **welcome**, please see [CONTRIBUTING] for additional details.

## 🧪 Testing

To run the tests you'll have to start the included node based server first if any in a separate terminal window.

With the server running, you can start testing.

```bash
composer test
```

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 📄 License

**biurad/php-framework** is licensed under the BSD-3 license. See the [`LICENSE`](LICENSE) file for more details.

[@divineniiquaye]: https://github.com/divineniiquaye
[UPGRADE]: UPGRADE.md
[CHANGELOG]: CHANGELOG.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/divineniiquaye/php-framework/contributors
[PHP]: https://php.net
[Composer]: https://getcomposer.org/
[PHP-Rade]: https://github.com/divineniiquaye/php-rade
[RoadRunner]: https://github.com/spiral/roadrunner
[ReactPHP]: https://github.com/reactphp/reactphp
[AmPHP]: https://github.com/amphp/http-server
[Swoole]: https://www.swoole.co.uk/
[Distributed Chat App]: https://github.com/biurad/spacechat
[Distributed Blog App]: https://github.com/biurad/spaceblog
[Symfony Like Blog App]: https://github.com/divineniiquaye/rade-blog
[sf-demo]: https://github.com/symfony/demo
[SOC]: https://en.wikipedia.org/wiki/Separation_of_concerns
[MVC]: https://en.wikipedia.org/wiki/Model-view-controller
