<?php /** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\RememberMe;

use DateTime;
use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;

/**
 * Concrete implementation of the RememberMeServicesInterface which needs
 * an implementation of TokenProviderInterface for providing remember-me
 * capabilities.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PersistentTokenBasedRememberMeServices extends AbstractRememberMeServices
{
    /** @var TokenProviderInterface */
    private $tokenProvider;

    public function setTokenProvider(TokenProviderInterface $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function cancelCookie(Request $request)
    {
        // Delete cookie on the client
        parent::cancelCookie($request);

        // Delete cookie from the tokenProvider
        $cookies = $request->getCookieParams();
        if (isset($cookies[$this->options['name']]) && null !== ($cookie = $cookies[$this->options['name']])
            && 2 === count($parts = $this->decodeCookie($cookie))
        ) {
            [$series] = $parts;
            $this->tokenProvider->deleteTokenBySeries($series);
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function processAutoLoginCookie(array $cookieParts, Request $request)
    {
        if (2 !== count($cookieParts)) {
            throw new AuthenticationException('The cookie is invalid.');
        }

        [$series, $tokenValue] = $cookieParts;
        $persistentToken = $this->tokenProvider->loadTokenBySeries($series);

        if (!hash_equals($persistentToken->getTokenValue(), $tokenValue)) {
            throw new CookieTheftException('This token was already used. The account is possibly compromised.');
        }

        if ($persistentToken->getLastUsed()->getTimestamp() + $this->options['lifetime'] < time()) {
            throw new AuthenticationException('The cookie has expired.');
        }

        $tokenValue = base64_encode(random_bytes(64));
        $this->tokenProvider->updateToken($series, $tokenValue, new DateTime());
        $this->cookie->addCookie(
            $this->options['name'],
            $this->encodeCookie([$series, $tokenValue]),
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            $this->options['samesite'] ?? null,
            null, time() + $this->options['lifetime']
        );

        return $this->getUserProvider($persistentToken->getClass())->loadUserByUsername($persistentToken->getUsername());
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function onLoginSuccess(Request $request, TokenInterface $token)
    {
        $series = base64_encode(random_bytes(64));
        $tokenValue = base64_encode(random_bytes(64));

        $this->tokenProvider->createNewToken(
            new PersistentToken(
                get_class($user = $token->getUser()),
                $user->getUsername() ?? $user->getEmail(),
                $series,
                $tokenValue,
                new DateTime()
            )
        );

        $this->cookie->addCookie(
            $this->options['name'],
            $this->encodeCookie([$series, $tokenValue]),
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            $this->options['samesite'] ?? null,
            null, time() + $this->options['lifetime']
        );
    }
}
