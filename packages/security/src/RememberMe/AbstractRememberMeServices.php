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

use BiuradPHP\Http\Interfaces\QueueingCookieInterface;
use BiuradPHP\Security\Interfaces\LogoutHandlerInterface;
use BiuradPHP\Security\Interfaces\RememberMeServicesInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Base class implementing the RememberMeServicesInterface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractRememberMeServices implements RememberMeServicesInterface, LogoutHandlerInterface
{
    const COOKIE_DELIMITER = ':';

    protected $logger;
    protected $cookie;
    protected $options = [
        'secure' => false,
        'httponly' => true,
    ];
    private $providerKey;
    private $secret;
    private $userProviders;

    /**
     * @param array $userProviders
     * @param string $secret
     * @param string $providerKey
     * @param array $options
     * @param QueueingCookieInterface $cookie
     * @param LoggerInterface|null $logger
     */
    public function __construct(array $userProviders, string $secret, string $providerKey, array $options = [], QueueingCookieInterface $cookie, LoggerInterface $logger = null)
    {
        if (empty($secret)) {
            throw new InvalidArgumentException('$secret must not be empty.');
        }
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }
        if (0 === count($userProviders)) {
            throw new InvalidArgumentException('You must provide at least one user provider.');
        }

        $this->userProviders = $userProviders;
        $this->secret = $secret;
        $this->cookie = $cookie;
        $this->providerKey = $providerKey;
        $this->options = array_merge($this->options, $options);
        $this->logger = $logger;
    }

    /**
     * Returns the parameter that is used for checking whether remember-me
     * services have been requested.
     *
     * @return string
     */
    public function getRememberMeParameter()
    {
        return $this->options['remember_me_parameter'];
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Implementation of RememberMeServicesInterface. Detects whether a remember-me
     * cookie was set, decodes it, and hands it to subclasses for further processing.
     *
     * @param Request $request
     *
     * @return TokenInterface|null
     * @throws Exception
     */
    final public function autoLogin(Request $request): ?TokenInterface
    {
        $cookies = $request->getCookieParams();

        if (! array_key_exists($this->options['name'], $cookies)) {
            return null;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie detected.');
        }

        $cookieParts = $this->decodeCookie($cookies[$this->options['name']]);

        try {
            $user = $this->processAutoLoginCookie($cookieParts, $request);

            if (!$user instanceof UserInterface) {
                throw new RuntimeException('processAutoLoginCookie() must return a UserInterface implementation.');
            }

            if (null !== $this->logger) {
                $this->logger->info('Remember-me cookie accepted.');
            }

            return new RememberMeToken($user, $this->providerKey, $this->secret);
        } catch (CookieTheftException $e) {
            $this->loginFail($request, $e);

            throw $e;
        } catch (UsernameNotFoundException $e) {
            if (null !== $this->logger) {
                $this->logger->info('User for remember-me cookie not found.', ['exception' => $e]);
            }

            $this->loginFail($request, $e);
        } catch (UnsupportedUserException $e) {
            if (null !== $this->logger) {
                $this->logger->warning('User class for remember-me cookie not supported.', ['exception' => $e]);
            }

            $this->loginFail($request, $e);
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember-Me authentication failed.', ['exception' => $e]);
            }

            $this->loginFail($request, $e);
        } catch (Exception $e) {
            $this->loginFail($request, $e);

            throw $e;
        }

        return null;
    }

    /**
     * Implementation for LogoutHandlerInterface. Deletes the cookie.
     * @param Request $request
     * @param ResponseInterface $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, ResponseInterface $response, TokenInterface $token)
    {
        $this->cancelCookie($request);
    }

    /**
     * Implementation for RememberMeServicesInterface. Deletes the cookie when
     * an attempted authentication fails.
     *
     * @param Request $request
     * @param Exception|null $exception
     */
    final public function loginFail(Request $request, Exception $exception = null)
    {
        $this->cancelCookie($request);
        $this->onLoginFail($request, $exception);
    }

    /**
     * Implementation for RememberMeServicesInterface. This is called when an
     * authentication is successful.
     * @param Request $request
     * @param TokenInterface $token
     */
    final public function loginSuccess(Request $request, TokenInterface $token)
    {
        // Make sure any old remember-me cookies are cancelled
        $this->cancelCookie($request);

        if (!$token->getUser() instanceof UserInterface) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember-me ignores token since it does not contain a UserInterface implementation.');
            }

            return;
        }

        if (!$this->isRememberMeRequested($request)) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember-me was not requested.');
            }

            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Remember-me was requested; setting cookie.');
        }

        // Remove attribute from request that sets a NULL cookie.
        // It was set by $this->cancelCookie()
        // (cancelCookie does other things too for some RememberMeServices
        // so we should still call it at the start of this method)
        //$this->cookie->remove($this->options['name']);

        $this->onLoginSuccess($request, $token);
    }

    /**
     * Subclasses should validate the cookie and do any additional processing
     * that is required. This is called from autoLogin().
     *
     * @param array $cookieParts
     * @param Request $request
     * @return UserInterface
     */
    abstract protected function processAutoLoginCookie(array $cookieParts, Request $request);

    /**
     * @param Request $request
     * @param Exception|null $exception
     */
    protected function onLoginFail(Request $request, Exception $exception = null)
    {
    }

    /**
     * This is called after a user has been logged in successfully, and has
     * requested remember-me capabilities. The implementation usually sets a
     * cookie and possibly stores a persistent record of it.
     *
     * @param Request $request
     * @param TokenInterface $token
     */
    abstract protected function onLoginSuccess(Request $request, TokenInterface $token);

    final protected function getUserProvider(string $class): UserProviderInterface
    {
        foreach ($this->userProviders as $provider) {
            if ($provider->supportsClass($class)) {
                return $provider;
            }
        }

        throw new UnsupportedUserException(sprintf('There is no user provider for user "%s". Shouldn\'t the "supportsClass()" method of your user provider return true for this classname?', $class));
    }

    /**
     * Decodes the raw cookie value.
     *
     * @param string $rawCookie
     * @return array
     */
    protected function decodeCookie(string $rawCookie)
    {
        return explode(self::COOKIE_DELIMITER, base64_decode($rawCookie));
    }

    /**
     * Encodes the cookie parts.
     *
     * @param array $cookieParts
     * @return string
     *
     */
    protected function encodeCookie(array $cookieParts)
    {
        foreach ($cookieParts as $cookiePart) {
            if (!is_int($cookiePart) && false !== strpos($cookiePart, self::COOKIE_DELIMITER)) {
                throw new InvalidArgumentException(sprintf('$cookieParts should not contain the cookie delimiter "%s"', self::COOKIE_DELIMITER));
            }
        }

        return base64_encode(implode(self::COOKIE_DELIMITER, $cookieParts));
    }

    /**
     * Deletes the remember-me cookie.
     * @param Request $request
     */
    protected function cancelCookie(Request $request)
    {
        if (!array_key_exists($this->options['name'], $request->getCookieParams())) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Clearing remember-me cookie.', ['name' => $this->options['name']]);
        }

        $this->cookie->addCookie($this->options['name'], 'logout', $this->options['path'], $this->options['domain'], $this->options['secure'] ?? $request->isSecure(), $this->options['httponly'], $this->options['samesite'] ?? null, null, 1);
    }

    /**
     * Checks whether remember-me capabilities were requested.
     *
     * @param Request $request
     * @return bool
     */
    protected function isRememberMeRequested(Request $request)
    {
        if (true === $this->options['always_remember_me']) {
            return true;
        }

        $cookie = $request->getParsedBody();
        $parameter = isset($cookie[$this->options['remember_me_parameter']]) ? $cookie[$this->options['remember_me_parameter']] : null;

        if (null === $parameter && null !== $this->logger) {
            $this->logger->debug('Did not send remember-me cookie.', ['parameter' => $this->options['remember_me_parameter']]);
        }

        return 'true' === $parameter || 'on' === $parameter || '1' === $parameter || 'yes' === $parameter || true === $parameter;
    }
}
