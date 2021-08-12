<div align="center">

# The Biurad PHP Library Template

[![PHP Version](https://img.shields.io/packagist/php-v/biurad/php-starter.svg?style=flat-square&colorB=%238892BF)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/biurad/php-starter.svg?style=flat-square)](https://packagist.org/packages/biurad/php-starter)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-starter/build?style=flat-square)](https://github.com/biurad/php-starter/actions?query=workflow%3Abuild)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-starter?style=flat-square)](https://codeclimate.com/github/biurad/php-starter)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-starter?style=flat-square)](https://codecov.io/gh/biurad/php-starter)
[![Psalm Type Coverage](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fbiurad%2Fphp-starter%2Fcoverage)](https://shepherd.dev/github/biurad/php-starter)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-starter.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-starter)

</div>

---

**biurad/php-starter** is a php library template repository for biurad lap. To use this library, edit contents to your preferred library name and license. As to why this project exist, it's to serve as a template for future open source PHP library projects. Of course, feel free to fork it and make your own recipe.

This project adheres to a [code of conduct](CODE_OF_CONDUCT.md). By participating in this project and its community, you are expected to uphold this code.

<!--- > This library is out of maintenance ir discontinued as it has reach its feature limit or end of life,  Updates will no longer be committed unless a **severe security venerability** is reported. -->

## 📦 Installation & Basic Usage

This project requires [PHP] 7.4 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/php-starter
```

<!-- USAGE_START -->
Write a bit of **How To** use this package, so developers can have a bit of idea about the repository before checking out documentation.
<!-- USAGE_END -->

## 📓 Documentation

For in-depth documentation before using this library. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com][docs].

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE] if available.

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

Any classes or methods marked `@internal` are not intended for use outside of this library and are subject to breaking changes at any time, so please avoid using them.

## 🛠️ Maintenance & Support

(This policy may change in the future and exceptions may be made on a case-by-case basis.)

- A new **patch version released** (e.g. `1.0.10`, `1.1.6`) comes out roughly every month. It only contains bug fixes, so you can safely upgrade your applications.
- A new **minor version released** (e.g. `1.1`, `1.2`) comes out every six months: one in June and one in December. It contains bug fixes and new features, but it doesn’t include any breaking change, so you can safely upgrade your applications;
- A new **major version released** (e.g. `1.0`, `2.0`, `3.0`) comes out every two years. It can contain breaking changes, so you may need to do some changes in your applications before upgrading.

When a **major** version is released, the number of minor versions is limited to five per branch (X.0, X.1, X.2, X.3 and X.4). The last minor version of a branch (e.g. 1.4, 2.4) is considered a **long-term support (LTS) version** with lasts for more that 2 years and the other ones cam last up to 8 months:

**Get a professional support from [Biurad Lap](https://biurad.com) after the active maintenance of a released version has ended**.

## 🧪 Testing

```bash
$ composer test
```

This will tests biurad/php-starter will run against PHP 7.4 version or higher.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

Contributions are welcome 👷‍♀️! To contribute, please familiarize yourself with our [CONTRIBUTING] guidelines.

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 📄 License

The **biurad/php-starter** library is copyright © [Divine Niiquaye Ibok](https://divinenii.com) and licensed for use under the[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE).

[PHP]: https://php.net
[Composer]: https://getcomposer.org
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/php-starter
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/biurad/php-starter/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
