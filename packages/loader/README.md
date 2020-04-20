# This dependency is a robust package for loading files, classes, class aliases, annotations, and store data loader for saving and accessing data (scalar, object, array, etc)

This dependency provides a few fluent and convenient wrappers for working files, class aliases, annotations, classes with a data loader that collect's data (scalar, object, array) and then populate's the data into object mapping.

**`Please note that this documentation is currently work-in-progress. Feel free to contribute.`**

## Installation

The recommended way to install Loader Manager is via Composer:

```bash
composer require biurad/biurad-loader
```

It requires PHP version 7.0 and supports PHP up to 7.4. The dev-master version requires PHP 7.1.

## How To Use

Loader manager offers a very intuitive API for scalables, null, array and object manipulation. Before we show you the first example, we need to think about how to pack all our configurations into one collection and easily access it as string, object or and array without having to define configurations over and over. Including loading classes, class aliases, or annotations.

All Loader manager classes are very well optimized for performance and in the first place, it provides full atomicity of operations.

Before using this dependency, take some time to familiarize yourself with [Php ArrayAccess](http://php.net/manual/en/arrayaccess.php), [Doctrine Annotations](https://github.com/doctrine/annotations), [PHP class_alias function](http://php.net/manual/en/function.class-alias.php), [PHP RecursiveDirectoryIterator](http://php.net/manual/en/recursivedirectoryiterator.php) and [PHP SplFileInfo](http://php.net/manual/en/splfileinfo.php). and handling stored variables in php.

The Loader Manager has some useful classes and methods which you may require while working on a project.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server if any first in a separate terminal window.

With the server running, you can start testing.

```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please report using the issue tracker.
use our example [Issue Report](.github/ISSUE_TEMPLATE/Bug_report.md) template.

## Want to be listed on our projects website

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a message on our website, mentioning which of our package(s) you are using.

Post Here: [Project Patreons - https://patreons.biurad.com](https://patreons.biurad.com)

We publish all received request's on our website;

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/biurad-loader/contributers)

## Support us

`Biurad Lap` is a technology agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.
