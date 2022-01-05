<div align="center">

# The PHP HTTP GALAXY

[![PHP Version](https://img.shields.io/packagist/php-v/biurad/http-galaxy.svg?style=flat-square&colorB=%238892BF)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/biurad/php-http-galaxy.svg?style=flat-square)](https://packagist.org/packages/biurad/php-http-galaxy)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-http-galaxy/build?style=flat-square)](https://github.com/biurad/php-http-galaxy/actions?query=workflow%3Abuild)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-http-galaxy?style=flat-square)](https://codeclimate.com/github/biurad/php-http-galaxy)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-http-galaxy?style=flat-square)](https://codecov.io/gh/biurad/php-http-galaxy)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-http-galaxy.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-http-galaxy)

</div>

---

**biurad/http-galaxy** is a fast, and simple [PSR-7] implementation for [PHP] 7.2+ based on [symfony/http-foundation] created by [Divine Niiquaye][@divineniiquaye]. This library seeks to add [PSR-7] support directly to [symfony/http-foundation] allowing the developer accessing both objects in one. It also shipped with [PSR-15] and [PSR-17] support.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.3 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/http-galaxy
```

## 📓 Documentation

For in-depth documentation before using this library.. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com][docs].

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE].

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

Any classes or methods marked `@internal` are not intended for use outside of this library and are subject to breaking changes at any time, so please avoid using them.

## 🛠️ Maintenance & Support

(This policy may change in the future and exceptions may be made on a case-by-case basis.)

- A new **patch version released** (e.g. `1.0.10`, `1.1.6`) comes out roughly every month. It only contains bug fixes, so you can safely upgrade your applications.
- A new **minor version released** (e.g. `1.1`, `1.2`) comes out every six months: one in June and one in December. It contains bug fixes and new features, but it doesn’t include any breaking change, so you can safely upgrade your applications;
- A new **major version released** (e.g. `1.0`, `2.0`, `3.0`) comes out every two years. It can contain breaking changes, so you may need to do some changes in your applications before upgrading.

When a **major** version is released, the number of minor versions is limited to five per branch (X.0, X.1, X.2, X.3 and X.4). The last minor version of a branch (e.g. 1.4, 2.4) is considered a **long-term support (LTS) version** with lasts for more that 2 years and the other ones cam last up to 8 months:

**Get a professional support from [Biurad Lap][] after the active maintenance of a released version has ended**.

## 🧪 Testing

```bash
$ ./vendor/bin/phpunit
```

This will tests biurad/http-galaxy will run against PHP 7.3 version or higher.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Contributions are welcome 👷‍♀️! To contribute, please familiarize yourself with our [CONTRIBUTING] guidelines.

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 📄 License

The **biurad/http-galaxy** library is copyright © [Divine Niiquaye Ibok](https://divinenii.com) and licensed for use under the [![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE).

[PHP]: https://php.net
[PSR-7]: http://www.php-fig.org/psr/psr-7/
[PSR-15]: http://www.php-fig.org/psr/psr-15/
[PSR-17]: http://www.php-fig.org/psr/psr-17/
[symfony/http-foundation]: https://github.com/symfony/http-foundation
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/php/http-galaxy
[commit]: https://commits.biurad.com/flight-routing.git
[UPGRADE]: UPGRADE.md
[CHANGELOG]: CHANGELOG.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/divineniiquaye/php-rade/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
