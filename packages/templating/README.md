<div align="center">

# The Biurad PHP Template UI

[![PHP Version](https://img.shields.io/packagist/php-v/biurad/templating.svg?style=flat-square&colorB=%238892BF)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/biurad/templating.svg?style=flat-square)](https://packagist.org/packages/biurad/templating)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-templating/build?style=flat-square)](https://github.com/biurad/php-templating/actions?query=workflow%3Abuild)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-templating?style=flat-square)](https://codeclimate.com/github/biurad/php-templating)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-templating?style=flat-square)](https://codecov.io/gh/biurad/php-templating)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-templating.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-templating)

</div>

---

**biurad/php-templating** is server side template ui for [PHP] 7.2+ created by [Divine Niiquaye][@divineniiquaye]. This library provides all the tools needed in working with any kind of template system and offers a structured solution to implement server side renderable template engines (Twig, Stempler, Latte, Blade, or native PHP templates) segregation.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.2 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/templating
```

This library is shipped out of the box with three high performance and dynamic renders for fast server side templating. It also support multiple rendering (can render templates declared for different renderers all at once).

Again you don't have to worry about declaring absolute path to every single template file. Set the storage to where paths can be found, enter the name of the template file. The rest will be taken care of return the rendered result (ultimate HTML).

```php
use Biurad\UI\Renders\PhpNativeRender;
use Biurad\UI\Helper\SlotsHelper;
use Biurad\UI\FilesystemStorage;
use Biurad\UI\Template;

$filesystemLoader = new FilesystemStorage(__DIR__.'/views');
$templating = new Template($filesystemLoader);

// Before adding a template renderer, you can add a namespace path
// $templating->addNamespace('MyBundle', __DIR__ . '/vendor/company/package/Resources');

// Add a template compiler renderer to Template.
$phpRenderEngine = new PhpNativeRender();
$templating->addRender($phpRenderEngine);

// You can also render an absolute path except for the fact that, it is not cacheable.
echo $templating->render('hello', ['firstname' => 'Divine']);

// hello.phtml or hello.php or hello.html
Hello, <?= $this->escape($firstname) ?>!
```

## 📓 Documentation

For in-depth documentation before using this library.. Full documentation on advanced usage, configuration, and customization can be found at [docs.divinenii.com][docs].

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

This will tests divineniiquaye/php-rade will run against PHP 7.4 version or higher.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Contributions are welcome 👷‍♀️! To contribute, please familiarize yourself with our [CONTRIBUTING] guidelines.

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 📄 License

The **divineniiquaye/php-rade** library is copyright © [Divine Niiquaye Ibok](https://divinenii.com) and licensed for use under the [![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE).

[PHP]: https://php.net
[Composer]: https://getcomposer.org
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/php/templating
[commit]: https://commits.biurad.com/php-templating.git
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/biurad/php-templating/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
