<div align="center">

# The Poakium Http Galaxy

[![Latest Version](https://img.shields.io/packagist/v/biurad/http-galaxy?include_prereleases&label=Latest&style=flat-square)](https://packagist.org/packages/biurad/http-galaxy)
[![Workflow Status](https://img.shields.io/github/actions/workflow/status/biurad/poakium/ci.yml?branch=master&label=Workflow&style=flat-square)](https://github.com/biurad/poakium/actions?query=workflow)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?&label=Poakium&style=flat-square)](LICENSE)
[![Maintenance Status](https://img.shields.io/maintenance/yes/2023?label=Maintained&style=flat-square)](https://github.com/biurad/poakium)

</div>

---

A [PHP][1] library that designed to provide [PSR-7][2], [PSR-15][3] and [PSR-17][4] seamless integration with [symfony/http-foundation][5] for your projects.

## 📦 Installation

This project requires [PHP][1] 7.4 or higher. The recommended way to install, is via [Composer][6]. Simply run:

```bash
$ composer require biurad/http-galaxy
```

## 📍 Quick Start

Since [symfony/http-foundation][5] library is a standard on it's own, libraries which relies on PHP-FIG standard makes it difficult to integrate with. With this library you can safely use PHP-FIG standards with good performance. build quickly using HTTP Galaxy.

Here is an example of how to use the library:

```php
use Biurad\Http\Factory\Psr17Factory;
use Biurad\Http\Middlewares\PrepareResponseMiddleware;
use Biurad\Http\Response;
use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

// Create a PSR-7 Request
$request = Psr17Factory::fromGlobalRequest();

// Create a PSR-15 Request Handler
$dispatcher = new MiddlewarePipe();
$dispatcher->pipe(new PrepareResponseMiddleware());
$dispatcher->pipe(
    new CallableMiddlewareDecorator(
        function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            // Apply middleware logic here
            return $handler->handle($request);
        }
    )
);

// A request handler handling application's logic
$handler = new \App\MyRequestHandler();

// Process the request handler and middleware(s)
$response = $dispatcher->process($request, $handler);
\assert($response instanceof Response);

// Send the response to the client from symfony's response object
$response->getResponse()->send();
```

## 📓 Documentation

In-depth documentation on how to use this library can be found at [docs.biurad.com][7]. It is also recommended to browse through unit tests in the [tests](./tests/) directory.

## 🙌 Sponsors

If this library made it into your project, or you interested in supporting us, please consider [donating][8] to support future development.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][9] is the author this library.
- [All Contributors][10] who contributed to this project.

## 📄 License

Poakium HTTP Galaxy is completely free and released under the [BSD 3 License](LICENSE).

[1]: https://php.net
[2]: http://www.php-fig.org/psr/psr-7/
[3]: http://www.php-fig.org/psr/psr-15/
[4]: http://www.php-fig.org/psr/psr-17/
[5]: https://github.com/symfony/http-foundation
[6]: https://getcomposer.org
[7]: https://docs.biurad.com/poakium/http-galaxy
[8]: https://biurad.com/sponsor
[9]: https://github.com/divineniiquaye
[10]: https://github.com/biurad/php-http-galaxy/contributors
