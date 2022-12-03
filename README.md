<h1 align="center">Poakium</h1>

<p align="center">
<a href="https://github.com/biurad/poakium/actions?query=workflow%3Abuild"><img src="https://img.shields.io/github/workflow/status/biurad/poakium/build?style=flat-square" alt="GitHub Actions"></a>
<a href="https://codecov.io/gh/biurad/poakium"><img src="https://codecov.io/gh/biurad/paokium/branch/5.x/graph/badge.svg" alt="CodeCoverage"></a>
<a href="https://packagist.org/packages/biurad/poakium"><img src="https://img.shields.io/packagist/v/biurad/poakium.svg" alt="Released"></a>
</p>

## 🧱 About

Poakium is a monorepo of reuseable PHP independent libraries developed by [Biurad Lap][1] for creating scalable and maintainable web and console applications. Visit our official website [here][1] for more information.

## 📦 Installation

* Install libraries with Composer (see requirements in the `composer.json` file).
* All libraries follows the [semantic versioning][2] strictly, publishes "Long Term Support" (LTS) versions and has a release process that is predictable and business-friendly.

## 🙌 Sponsor

Poakium is backed by [DivineNii][3] and [Biurad Lap][4].

**DivineNii** is a software engineer who is passionate about open source and the PHP community. He provides a wide range of professional services, kindly contact him at [divinenii.com][3].

**Biurad Lap** is a tech company/agency in Ghana, providing software development, cloud solutions, consulting, and training services. Visit their [website][4] for more information.

Help Poakium by [sponsoring][5] its development!


## 📚 Documentation

Follow the instructions in [SETUP.md][6] to get a development environment set up.

* Read the [Documentation guide][7] for full details on how to use poakium libraries.
* Read the [Contributing guide][8] for full details on how to contribute to Poakium.
* All communication and contributions to the Poakium are subject to our [Code of Conduct][9].

## 📂 Repo Structure

The repository has the following packages (sub projects):

* [Git-SCM](https://github.com/biurad/php-git-scm) - tools/git-scm
* [Monorepo](https://github.com/biurad/php-monorepo) - tools/monorepo
* [Annotations](https://github.com/biurad/php-annotations) - packages/annotations
* [Cache](https://github.com/biurad/php-cache) - packages/cache
* [Coding Standard](https://github.com/biurad/php-coding-standard) - packages/coding-standard
* [Docs](https://github.com/biurad/php-docs) - packages/docs
* [Http Galaxy](https://github.com/biurad/php-http-galaxy) - packages/http-galaxy
* [Loader](https://github.com/biurad/php-loader) - packages/loader
* [Maker](https://github.com/biurad/php-make) - packages/maker
* [Starter](https://github.com/biurad/php-starter) - packages/starter
* [Templating](https://github.com/biurad/php-templating) - packages/templating

**Please do not use biurad/poakium in production!** Use the split packages instead, unless your project heavily relies on it.

## 📜License

Poakium is open-sourced software and licensed under the [MIT][10] license.


[1]: https://biurad.com
[2]: https://semver.org
[3]: https://divinenii.com
[4]: https://biurad.com
[5]: https://biurad.com/sponser
[6]: ./SETUP.md
[7]: https://docs.poakium.com
[8]: ./CONTRIBUTING.md
[9]: ./CODE_OF_CONDUCT.md
[10]: ./LICENSE
