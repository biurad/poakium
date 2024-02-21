<div align="center">

# The Poakium Database

[![Latest Version](https://img.shields.io/packagist/v/biurad/database.svg?style=flat-square)](https://packagist.org/packages/biurad/database)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-git-scm/build?style=flat-square)](https://github.com/biurad/php-database/actions)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-database?style=flat-square)](https://codeclimate.com/github/biurad/php-database)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-database?style=flat-square)](https://codecov.io/gh/biurad/php-git-scm)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-database.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-database)

</div>

---

A Blazing fast database abstraction layer (DBAL) with object–relational mapping(ORM) support PHP[1] library for working with PHP Data Objects(PDO) databases. It is a simple, easy to use, and powerful tool for querying data into databases.

## 📦 Installation

PHP[1] 8.0 or newer and SQL Server (2008 R2 or higher), MySQL (5.7 or higher), Postgres (12.12 or higher) and Oracle (12.1 or higher) are required. The recommended way to install, is by using [Composer][2]. Simply run:

```bash
$ composer require biurad/database
```

## 📍 Quick Start

Here is an example of how to use the library:

A plain PDO example of this:

```php
$stmt = $pdo->prepare("SELECT * FROM myTable WHERE name = ?");
$stmt->bindParam(1, $name, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->get_result();
$products=[];

while($row = $result->fetch_assoc()) $product[] = $row;
$stmt->close();
```

Turns into this using query builder:

```php
$products = $connection->getQueryBuilder()
    ->select()
    ->from('myTable')
    ->where('name', '=', $name)
    ->fetchList();

// or
$products = $pdo->fetchList("SELECT * FROM myTable WHERE name = ?", $name);
```

or using the query builder with the ORM:

```php
$products = $connection->getQueryBuilder()
    ->select()
    ->from(Entity\Product::class)
    ->where('name', '=', $name)
    ->fetchList();

// or
$products = $connection->getRepository(ProductRepository::class)
    ->findBy(['name' => $name]);
```

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][3]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][4] to support future development.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][5] is the author this library.
- [All Contributors][6] who contributed to this project.

## 📄 License

Poakium Database is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://php.net
[2]: https://getcomposer.org
[3]: https://docs.biurad.com/poakium/database
[4]: https://biurad.com/sponsor
[5]: https://github.com/divineniiquaye
[6]: https://github.com/biurad/php-database/contributors
