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
use Symfony\Component\Security\Core\Authentication\RememberMe\InMemoryTokenProvider;
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
    public const USERS_ID = '_remember_user_id';

    private ?TokenProviderInterface $tokenProvider;
    private ?TokenVerifierInterface $tokenVerifier;
    private ?SignatureHasher $signatureHasher;
    private Cookie $cookie;
    private string $secret, $parameterName, $usersIdCookie;

    public function __construct(
        string $secret,
        TokenProviderInterface $tokenProvider = null,
        TokenVerifierInterface $tokenVerifier = null,
        SignatureHasher $signatureHasher = null,
        string $requestParameter = '_remember_me',
        string $usersIdCookie = '_remember_user_id',
        array $options = []
    ) {
        $this->secret = $secret;
        $this->usersIdCookie = $usersIdCookie;
        $this->parameterName = $requestParameter;
        $this->tokenProvider = $tokenProvider ?? new InMemoryTokenProvider();
        $this->tokenVerifier = $tokenVerifier ?? ($this->tokenProvider instanceof TokenVerifierInterface ? $this->tokenProvider : null);
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

    public function getUsersIdCookie(): string
    {
        return $this->usersIdCookie;
    }

    /**
     * Returns the user and for every 2 minutes a new remember me cookie is included.
     *
     * @return array $user {0: UserInterface, 1: Cookie|null}
     */
    public function consumeRememberMeCookie(string $rawCookie, UserProviderInterface $userProvider): array
    {
        [, $identifier, $expires, $value] = self::fromRawCookie($rawCookie);

        if (!\str_contains($value, ':')) {
            throw new AuthenticationException('The cookie is incorrectly formatted.');
        }
        $user = $userProvider->loadUserByIdentifier($identifier);

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

            // if a token was regenerated less than 2 minutes ago, there is no need to regenerate it
            // if multiple concurrent requests reauthenticate a user we do not want to update the token several times
            if ($persistentToken->getLastUsed()->getTimestamp() + (60 * 2) < \time()) {
                $tokenValue = $this->generateHash();
                $tokenLastUsed = new \DateTime();

                if ($this->tokenVerifier) {
                    $this->tokenVerifier->updateExistingToken($persistentToken, $tokenValue, $tokenLastUsed);
                }
                $this->tokenProvider->updateToken($series, $tokenValue, $tokenLastUsed);

                return [$user, $this->createRememberMeCookie($user, $series . ':' . $tokenValue)];
            }
        } else {
            throw new \LogicException(\sprintf('Expected one of %s or %s class.', TokenProviderInterface::class, SignatureHasher::class));
        }

        return [$user, null];
    }

    public function createRememberMeCookie(UserInterface $user, string $value = null): Cookie
    {
        $expires = \time() + $this->cookie->getExpiresTime();
        $class = \get_class($user);
        $identifier = $user->getUserIdentifier();

        if (null !== $this->signatureHasher) {
            $value = $this->signatureHasher->computeSignatureHash($user, $expires);
        } elseif (null === $value) {
            if (null === $this->tokenProvider) {
                throw new \LogicException(\sprintf('Expected one of %s or %s class.', TokenProviderInterface::class, SignatureHasher::class));
            }

            $series = \base64_encode(\random_bytes(64));
            $tokenValue = $this->generateHash();
            $this->tokenProvider->createNewToken($token = new PersistentToken($class, $identifier, $series, $tokenValue, new \DateTime()));
            $value = $token->getSeries() . ':' . $token->getTokenValue();
        }

        $cookie = clone $this->cookie
            ->withValue(\base64_encode(\implode(self::COOKIE_DELIMITER, [$class, \base64_encode($identifier), $expires, $value])))
            ->withExpires($expires);

        return $this->setCookieName($cookie, $user->getUserIdentifier());
    }

    /**
     * @return array<int,Cookie>
     */
    public function clearRememberMeCookies(ServerRequestInterface $request): array
    {
        $cookies = [];
        $identifiers = \explode('|', \urldecode($request->getCookieParams()[$this->usersIdCookie] ?? '')) ?: [];

        foreach ($identifiers as $identifier) {
            $clearCookie = $this->cookie;

            if (null === $cookie = $request->getCookieParams()[$clearCookie->getName() . $identifier] ?? null) {
                continue;
            }

            if (null !== $this->tokenProvider) {
                $rememberMeDetails = self::fromRawCookie($cookie);
                [$series, ] = \explode(':', $rememberMeDetails[3]);
                $this->tokenProvider->deleteTokenBySeries($series);
            }

            $cookies[] = $this->setCookieName($clearCookie->withExpires(1)->withValue(null), $identifier);
        }

        return $cookies;
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

    private function setCookieName(Cookie $cookie, string $userId): Cookie
    {
        return \Closure::bind(function (Cookie $cookie) use ($userId) {
            $cookie->name .= $userId;

            return $cookie;
        }, $cookie, $cookie)($cookie);
    }

    private function generateHash(): string
    {
        return hash_hmac('sha256', \base64_encode(\random_bytes(64)), $this->secret);
    }
}
