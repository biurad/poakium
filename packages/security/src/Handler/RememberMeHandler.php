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

namespace Biurad\Security\Handler;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenVerifierInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;
use Symfony\Component\Security\Core\Signature\SignatureHasher;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * A remember handler to create and valid a user via a cookie.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RememberMeHandler
{
    public const COOKIE_DELIMITER = ':';
    public const REMEMBER_ME = '_security.remember_me';

    private ?TokenProviderInterface $tokenProvider;
    private ?TokenVerifierInterface $tokenVerifier;
    private ?SignatureHasher $signatureHasher;
    private Cookie $cookie;
    private string $secret;
    private string $parameterName;

    public function __construct(
        string $secret,
        TokenProviderInterface $tokenProvider = null,
        TokenVerifierInterface $tokenVerifier = null,
        SignatureHasher $signatureHasher = null,
        string $requestParameter = '_remember_me',
        array $options = []
    ) {
        $this->secret = $secret;
        $this->parameterName = $requestParameter;
        $this->tokenProvider = $tokenProvider;
        $this->tokenVerifier = $tokenVerifier;
        $this->signatureHasher = $signatureHasher;
        $this->cookie = new Cookie(
            $options['name'] ?? 'REMEMBER_ME',
            null,
            $options['lifetime'] ?? 31536000,
            $options['path'] ?? '/',
            $options['domain'] ?? null,
            $options['secure'] ?? false,
            $options['httponly'] ?? true,
            false,
            $options['samesite'] ?? null
        );
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getCookieName(): string
    {
        return $this->cookie->getName();
    }

    public function consumeRememberMeCookie(string $rawCookie, UserProviderInterface $userProvider): UserInterface
    {
        [, $identifier, $expires, $value] = self::fromRawCookie($rawCookie);

        if (!\str_contains($value, ':')) {
            throw new AuthenticationException('The cookie is incorrectly formatted.');
        }

        $user = $userProvider->loadUserByIdentifier($$identifier);

        if (!$user instanceof UserInterface) {
            throw new \LogicException(\sprintf('The UserProviderInterface implementation must return an instance of UserInterface, but returned "%s".', \get_debug_type($user)));
        }

        if (null !== $this->signatureHasher) {
            try {
                $this->signatureHasher->verifySignatureHash($user, $expires, $value);
            } catch (InvalidSignatureException $e) {
                throw new AuthenticationException('The cookie\'s hash is invalid.', 0, $e);
            } catch (ExpiredSignatureException $e) {
                throw new AuthenticationException('The cookie has expired.', 0, $e);
            }
        } elseif (null !== $this->tokenProvider) {
            [$series, $tokenValue] = \explode(':', $value);
            $persistentToken = $this->tokenProvider->loadTokenBySeries($series);

            if (null !== $this->tokenVerifier) {
                $isTokenValid = $this->tokenVerifier->verifyToken($persistentToken, $tokenValue);
            } else {
                $isTokenValid = \hash_equals($persistentToken->getTokenValue(), $tokenValue);
            }

            if (!$isTokenValid) {
                throw new CookieTheftException('This token was already used. The account is possibly compromised.');
            }

            if ($persistentToken->getLastUsed()->getTimestamp() + $this->cookie->getExpiresTime() < \time()) {
                throw new AuthenticationException('The cookie has expired.');
            }
        } else {
            throw new \LogicException(\sprintf('Expected one of %s or %s class.', TokenProviderInterface::class, SignatureHasher::class));
        }

        return $user;
    }

    public function createRememberMeCookie(UserInterface $user, bool $secure): Cookie
    {
        $expires = \time() + $this->cookie->getExpiresTime();
        $class = \get_class($user);
        $identifier = $user->getUserIdentifier();

        if (null !== $this->signatureHasher) {
            $value = $this->signatureHasher->computeSignatureHash($user, $expires);
        } elseif (null !== $this->tokenProvider) {
            $series = \base64_encode(\random_bytes(64));
            $tokenValue = \hash_hmac('sha256', \base64_encode(\random_bytes(64)), $this->secret);
            $this->tokenProvider->createNewToken($token = new PersistentToken($class, $identifier, $series, $tokenValue, new \DateTime()));
            $value = $token->getSeries() . ':' . $token->getTokenValue();
        } else {
            throw new \LogicException(\sprintf('Expected one of %s or %s class.', TokenProviderInterface::class, SignatureHasher::class));
        }

        $cookie = $this->cookie
            ->withSecure($secure)
            ->withValue(\base64_encode(\implode(self::COOKIE_DELIMITER, [$class, \base64_encode($identifier), $expires, $value])))
            ->withExpires($expires);

        return \Closure::bind(function (Cookie $cookie) use ($user) {
            $cookie->name = $cookie->name . $user->getUserIdentifier();

            return $cookie;
        }, $cookie, $cookie)($cookie);
    }

    public function clearRememberMeCookie(ServerRequestInterface $request): ?Cookie
    {
        if (null !== $this->tokenProvider) {
            $cookie = $request->getCookieParams()[$this->cookie->getName()] ?? null;

            if (null === $cookie) {
                return $cookie;
            }

            $rememberMeDetails = self::fromRawCookie($cookie);
            [$series, ] = \explode(':', $rememberMeDetails[3]);
            $this->tokenProvider->deleteTokenBySeries($series);
        }

        return $this->cookie->withExpires(1)->withValue(null);
    }

    private static function fromRawCookie(string $rawCookie): array
    {
        $cookieParts = \explode(self::COOKIE_DELIMITER, \base64_decode($rawCookie), 4);

        if (false === $cookieParts[1] = \base64_decode($cookieParts[1], true)) {
            throw new AuthenticationException('The user identifier contains a character from outside the base64 alphabet.');
        }

        if (4 !== \count($cookieParts)) {
            throw new AuthenticationException('The cookie contains invalid data.');
        }

        return $cookieParts;
    }
}
