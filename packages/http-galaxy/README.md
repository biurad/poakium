# Http Pipeline: abstraction for HTTP request, response, and csp protection plus more.

HTTP request and response are encapsulated in `BiuradPHP\Http\Request` and `BiuradPHP\Http\Response` objects which offer comfortable API and also act as
sanitization filter. This package uses [guzzlehttp/psr7](https://github.com/guzzlehttp/psr7).

**`Please note that you can get the documentation for this dependency on guzzlehttp website, psr7`**

## Installation

The recommended way to install Http Manager is via Composer:

```bash
composer require biurad/biurad-http
```

It requires PHP version 7.1 and supports PHP up to 7.4. The dev-master version requires PHP 7.2.

## How To Use

You have no limitation to what you can do with this package. This package has two factories that implements Psr17 http factories. This package is shipped with two http factories, thus `BiuradPHP\Http\Factory\GuzzleHttpPsr17Factory` and `BiuradPHP\Http\Factory\LaminasPs17Factory`, you can create you custom factory by extending it to abstract class `BiuradPHP\Http\Factory\Psr17Factory`.

If you are using a different package for psr7 http messages, and you want to migrate it this package, use our BiuradPHP\Http\Factory\Psr17Bridge.

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

We publish all received request's on our website.

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/biurad-http/contributers)

## Support us

`Biurad Lap` is a webdesign agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.
