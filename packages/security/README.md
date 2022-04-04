<div align="center">

# The Biurad PHP Security

[![PHP Version](https://img.shields.io/packagist/php-v/biurad/security.svg?style=flat-square&colorB=%238892BF)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/biurad/security.svg?style=flat-square)](https://packagist.org/packages/biurad/security)
[![Workflow Status](https://img.shields.io/github/workflow/status/biurad/php-security/build?style=flat-square)](https://github.com/biurad/php-security/actions?query=workflow%3Abuild)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/biurad/php-security?style=flat-square)](https://codeclimate.com/github/biurad/php-security)
[![Coverage Status](https://img.shields.io/codecov/c/github/biurad/php-security?style=flat-square)](https://codecov.io/gh/biurad/php-security)
[![Quality Score](https://img.shields.io/scrutinizer/g/biurad/php-security.svg?style=flat-square)](https://scrutinizer-ci.com/g/biurad/php-security)

</div>

**biurad/php-security** is a simple security authentication and authorization system for [PHP] 7.4+, developed using [Symfony's Security Core][sfs-core] and [Biurad's Http Galaxy][php-http-galaxy] with optional support for [Symfony's Security CSRF][sfs-csrf].

The goal of this project is to provide the same level of security [Symfony's Security Http][sfs-http] provides, but with great performance.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.4 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require biurad/security 1.*
```

Here is a simple example of how to use this library in your project:

```php
use Biurad\Security\Authenticator;
use Biurad\Security\Authenticator\FormLoginAuthenticator;
use Biurad\Security\Token\CacheableTokenStorage;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

require_once __DIR__ . '/vendor/autoload.php';

$accessDecisionManager = new AccessDecisionManager([
    new AuthenticatedVoter(new AuthenticationTrustResolver()),
    new RoleVoter(),
    new RoleHierarchyVoter(new RoleHierarchy([
        'ROLE_ADMIN' => ['ROLE_USER'],
    ]))
]);
$userProvider = new InMemoryUserProvider([
    'divine' => [
        'password' => 'foo',
        'enabled' => true,
        'roles' => ['ROLE_USER'],
    ],
]);
$hasherFactory = new PasswordHasherFactory([
    InMemoryUser::class => ['algorithm' => 'plaintext'],
    'common' => ['algorithm' => 'bcrypt'],
    'memory-hard' => ['algorithm' => 'sodium'],
]);
$tokenStorage = new CacheableTokenStorage($session = new Session());
$authenticators = [
    new FormLoginAuthenticator($userProvider, $hasherFactory, null, $session),
];

$request = \Biurad\Http\Factory\Psr17Factory::fromGlobalRequest();
$authenticator = new Authenticator($authenticators, $tokenStorage, $accessDecisionManager);

// The parameters which should be fetched from request ...
$credentials = ['_identifier', '_password'];

// To authenticate a user has logged in,
if (true !== $response = $authenticator->authenticate($request, $credentials)) {
    if ($response instanceof ResponseInterface) {
        // ... You can emit response to the browser.
    }

    throw new AccessDeniedException();
}

// The token is ready for use
$token = $authenticator->getToken();

```

All authenticators can be used without the authenticator class. The remember me authenticator usage is abit different from how symfony's remember me works.
From the example above, In order to use the remember me authenticator, a remember me handler needs has to be passed into the form login authenticator.

```php
use Biurad\Security\Handler\RememberMeHandler;
use Biurad\Security\Token\PdoTokenProvider;

$tokenProvider = new PdoTokenProvider('mysql:host=localhost;dbname=testing;username=root;password=password');
$rememberMeHandler = new RememberMeHandler('cookie-secret', $tokenProvider);
```

After passing the remember me handler into the form login authenticator, remember to fetch the remember me
cookies from the authenticated token. The following example shows how to fetch the remember me cookies:

```php
use Biurad\Security\Helper;

$rememberMeCookies = Helper::createRememberMeCookie($token, $request);
// The $rememberMeCookies is an array of cookies, which can be used to set the cookies into the response.
```

When the above examples are followed and executed, If a user is not in session, the user's token should be fetched
from the remember me cookies and set into the token storage. Here is an example:

```php
use Biurad\Security\Authenticator\RememberMeAuthenticator;

if (null === $token = $tokenStorage->getToken()) {
    $rememberAuth = new RememberMeAuthenticator($rememberMeHandler, $userProvider, true);

    if ($rememberAuth->supports($request)) {
        $token = $rememberAuth->authenticate($request, []);
        $tokenStorage->setToken($token);

        $rememberMeCookies = Helper::createRememberMeCookie($token, $request);
        // Incase cookies exists, set the cookies into the response.
    }
}

// The token is ready for use

```

## 📓 Documentation

For in-depth documentation before using this library. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com][docs].

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

This will tests biurad/php-security will run against PHP 7.4 version or higher.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Contributions are welcome 👷‍♀️! To contribute, please familiarize yourself with our [CONTRIBUTING] guidelines.

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 📄 License

The **biurad/php-security** library is copyright © [Divine Niiquaye Ibok](https://divinenii.com) and licensed for use under the [![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE).

[Composer]: https://getcomposer.org
[PHP]: https://php.net
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/php/security
[commit]: https://commits.biurad.com/php-security.git
[UPGRADE]: UPGRADE.md
[CHANGELOG]: CHANGELOG.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/biurad/php-security/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
[php-http-galaxy]: https://github.com/biurad/php-http-galaxy
[sfs-core]: https://github.com/symfony/security-core
[sfs-http]: https://github.com/symfony/security-http
[sfs-csrf]: https://github.com/symfony/security-csrf
