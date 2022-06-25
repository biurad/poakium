# Check & Fix Your Code with Biurad Coding Standard

This is set of [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer), [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer), [Psalm](https://github.com/vimeo/psalm) and [Phpstan](https://github.com/phpstan/phpstan) which is combined under this repository that **checks and fixes** your PHP code.

This package brings coding standard, static analysis and type support to your projects. Our goal is to increase developer's productivity and the application's overall health by finding as many coding standard and type-related bugs as possible.

>The main [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) and [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) code style is based on PSR-12 with custom rules to improve performance. To apply it in your project do the following:

## Install the package

```sh
composer require --dev biurad/coding-standard
#or
composer global require biurad/coding-standard
```

## Check the code for bugs

```sh
#vendor/bin/biurad-cs check <dir1> <dir2> <file1>....
vendor/bin/biurad-cs check src tests
```

## Automatically fix the code style

```sh
#vendor/bin/biurad-cs fix <dir1> <dir2> <file1>....
vendor/bin/biurad-cs fix src tests
```

> Use --help option to find out more about command usage
