<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

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
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

require __DIR__ . '/../../vendor/autoload.php';

$accessDecisionManager = new AccessDecisionManager([
    new AuthenticatedVoter(new AuthenticationTrustResolver()),
    new RoleVoter(),
    new RoleHierarchyVoter(new RoleHierarchy([
        'ROLE_ADMIN' => ['ROLE_USER'],
    ])),
]);
$userProvider = new InMemoryUserProvider([
    'divine' => [
        'password' => 'foo',
        'enabled' => false,
        'roles' => ['ROLE_USER'],
    ],
]);
$hasherFactory = new PasswordHasherFactory([
    'common' => ['algorithm' => 'bcrypt'],
    'memory-hard' => ['algorithm' => 'sodium'],
]);
$tokenStorage = new CacheableTokenStorage($session = new Session());
$authenticators = [
    new FormLoginAuthenticator($userProvider, $tokenStorage, $hasherFactory, null, $session),
];

$request = \Biurad\Http\Factory\Psr17Factory::fromGlobalRequest();
$authenticator = new Authenticator($authenticators, $tokenStorage, $accessDecisionManager);

// The parameters which should be fetched from request ...
$credentials = ['_username', '_password'];

// To authenticate a user has logged in,
if (true !== $response = $authenticator->authenticate($request, $credentials)) {
    if ($response instanceof ResponseInterface) {
        echo (string) $response->getBody();
    } else {
        throw new AccessDeniedException();
    }
}

// The token is ready for use
$token = $authenticator->getToken();

if ($token instanceof UsernamePasswordToken) {
    $user = $token->getUser();
    echo 'User: ' . $user->getUserIdentifier() . \PHP_EOL;

    // The user is authenticated
    if ($authenticator->isGranted(['ROLE_ADMIN'])) {
        echo 'You are an admin';
    }
}
