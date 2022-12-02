<div align="center">

# The Poakium Monorepo

[![Latest Version](https://img.shields.io/packagist/v/biurad/monorepo.svg?style=flat-square)](https://packagist.org/packages/biurad/monorepo)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-monorepo/build?style=flat-square)](https://github.com/biurad/php-monorepo/actions)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-monorepo?style=flat-square)](https://codeclimate.com/github/biurad/php-monorepo)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-monorepo?style=flat-square)](https://codecov.io/gh/biurad/php-monorepo)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-monorepo)

</div>

---

A PHP library for working with Monorepo Project's. This library handles splitting of sub folder commits and pushing of tags into multiple remote repositories which depends on a `.monorepo` config file in your monorepo's working directory.

## 📦 Installation

PHP 8.0 or newer and [GIT][1] 2.30 or newer are required. The recommended way to install, is by using [Composer][2]. Simply run:

```bash
$ composer require biurad/monorepo
```

## 📍 Quick Start

This library main purpose is for continuously splitting up a monorepo project into multiple repositories as read-only. Handles splitting of branches and tags, this library is truly extendable as you can define different worker classes for a particular job run. [splitsh/lite][3] is used under the hood for great performance.

> NB: It is highly recommended to have git filter-repo installed as this allows re-merging of multiple repositories as many times as you want, the default way merges once an can be very risky to re-merge.

In order to use this library, a `.monorepo` yaml syntax config file must exists in project working directory.
Here is an example of the *.monorepo* config:

```yaml
# URL or absolute path to the remote GIT repository of the monorepo
base_url: https://github.com/YOUR-VENDORNAME/YOUR-PROJECT.git

# All branches that match this regular expression will be split by default
branch_filter: /^(main|develop|\d+\.\d+)$/

# A list of workers which should run when the monorepo command is called
workers:
  main:
    - Biurad\Monorepo\Worker\SplitCommitsWorker
  #  - Custom\MonorepoWorker
  merge:
    - Biurad\Monorepo\Worker\MergeRepoWorker
  #release:
  #  - Biurad\Monorepo\Worker\PushNextDevWorker


# List of all split projects
repositories:
  # The first split project living in the folder /first-subfolder
  first-subfolder:
    # URL or absolute path to the remote GIT repository
    url: https://github.com/YOUR-VENDORNAME/YOUR-FIRST-SPLIT-PROJECT.git
  # Second split project living in the folder /second-subfolder
  second-subfolder:
    # URL or absolute path to the remote GIT repository
    url: https://github.com/YOUR-VENDORNAME/YOUR-SECOND-SPLIT-PROJECT.git
    # A path which exist in the root path where monorepo command is called
    # If not defined, this config key (second-subfolder) is used as path
    path: php-example
    # If true, Repo supports merging & splitting. If false, only splitting is supported
    merge: true

# An array of configuration's which custom workers may rely on
extra: ~
```

This library uses a single workflow command that takes in one argument which is the specified job name (default is *main*) and a bunch of options required by class workers. You can also use this library in your CI workflow, if you seek to have all commits and tags created while running this library in GitHub Actions CI signed. Then checkout [crazy-max/ghaction-import-gpg][4] repository to enable such feature.

To use run this library simply run this command in your terminal:

```bash
$ php vendor/bin/monorepo
```

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][5]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][6] to support future development.

## 👥 Credits & Acknowledgements

- [Martin Auswöger][7] developed the [contao/monorepo-tools][8] library which inspired this library.
- [Divine Niiquaye Ibok][9] is the author this library.
- [All Contributors][10] who contributed to this project.

## 📄 License

Poakium Monorepo is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://git-scm.com
[2]: https://getcomposer.org
[3]: https://githib.com/splitsh/lite
[4]: https://github.com/crazy-max/ghaction-import-gpg
[5]: https://docs.biurad.com/poakium/monorepo
[6]: https://biurad.com/sponsor
[7]: https://au.si/
[8]: https://github.com/contao/monorepo-tools
[9]: https://github.com/divineniiquaye
[10]: https://github.com/biurad/php-monorepo/contributors
