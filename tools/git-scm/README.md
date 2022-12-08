<div align="center">

# The Poakium Git-SCM

[![Latest Version](https://img.shields.io/packagist/v/biurad/git-scm.svg?style=flat-square)](https://packagist.org/packages/biurad/git-scm)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-git-scm?style=flat-square)](https://codeclimate.com/github/biurad/php-git-scm)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-git-scm?style=flat-square)](https://codecov.io/gh/biurad/php-git-scm)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-git-scm.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-git-scm)

</div>

---

A lightweight PHP library for working with Git Source Control Management (SCM). It is a simple, easy to use, and powerful tool for managing your Git repositories.

## 📦 Installation

PHP 8.0 or newer and [GIT][2] 2.30 or newer are required. The recommended way to install, is by using [Composer][1]. Simply run:

```bash
$ composer require biurad/git-scm
```

## 📍 Quick Start

This library is just a simple wrapper for [GIT][2] shell commands and parses the output so you can use it in your PHP code.
The performance is not as good as Git, but it is still fast enough to be used in production.

Here is an example of how to use the library:

```php
use Biurad\Git;

$repo = new Git\Repository('/path/to/repository');
$repo->commit(new Git\CommitNew(
    message: new Git\Commit\Message('My commit message'),
    author: $repo->getAuthor()->setDate('Tue, 06 Sep 2022 07:21:10')
)); // Stage all changes and commit them

$repo->getLog()->getCommits(); // Get all commits
$repo->getLastCommit(); // Get the newest commit

$branches = $repo->getBranches(); // Get all branches
$tags = $repo->getTags(); // Gt all tags
```

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][3]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][4] to support future development.

## 👥 Credits & Acknowledgements

- [Alexandre Salomé][5] developed the [gitonomy/gitlib][6] library which inspired this library.
- [Divine Niiquaye Ibok][7] is the author this library.
- [All Contributors][8] who contributed to this project.

## 📄 License

Git SCM is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://getcomposer.org
[2]: https://git-scm.com
[3]: https://docs.biurad.com/poakium/git-scm
[4]: https://biurad.com/sponsor
[5]: https://github.com/alexandresalome
[6]: https://github.com/gitonomy/gitlib
[7]: https://github.com/divineniiquaye
[8]: https://github.com/biurad/php-git-scm/contributors
