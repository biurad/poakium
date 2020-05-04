<?php

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

namespace BiuradPHP\Security\Firewalls;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Event\InteractiveLoginEvent;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * AbstractPreAuthenticatedListener is the base class for all listener that
 * authenticates users based on a pre-authenticated request (like a certificate
 * for instance).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
abstract class AbstractPreAuthenticatedListener extends AbstractListener
{
    protected $logger;
    private $tokenStorage;
    private $authenticationManager;
    private $providerKey;
    private $dispatcher;
    private $sessionStrategy;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, string $providerKey, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        $request = $event->getRequest();
        try {
            $event->setRequest($request->withAttribute('_pre_authenticated_data', $this->getPreAuthenticatedData($request)));
        } catch (BadCredentialsException $e) {
            $this->clearToken($e);

            return false;
        }

        return true;
    }

    /**
     * Handles pre-authentication.
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();

        [$user, $credentials] = $request->getAttribute('_pre_authenticated_data');
        $request = $request->withoutAttribute('_pre_authenticated_data');

        if (null !== $this->logger) {
            $this->logger->debug('Checking current security token.', ['token' => (string) $this->tokenStorage->getToken()]);
        }

        if (null !== $token = $this->tokenStorage->getToken()) {
            if ($token instanceof PreAuthenticatedToken && $this->providerKey == $token->getProviderKey() && $token->isAuthenticated() && $token->getUsername() === $user) {
                return;
            }
        }

        if (null !== $this->logger) {
            $this->logger->debug('Trying to pre-authenticate user.', ['username' => (string) $user]);
        }

        try {
            $token = $this->authenticationManager->authenticate(new PreAuthenticatedToken($user, $credentials, $this->providerKey));

            if (null !== $this->logger) {
                $this->logger->info('Pre-authentication successful.', ['token' => (string) $token]);
            }

            $this->migrateSession($request, $token);

            $this->tokenStorage->setToken($token);
            $event->setAuthenticationToken($token);

            if (null !== $this->dispatcher) {
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->dispatcher->dispatch($loginEvent);
            }
        } catch (AuthenticationException $e) {
            $this->clearToken($e);
            $event->setAuthenticationToken(null);
        }
    }

    /**
     * Call this method if your authentication token is stored to a session.
     *
     * @final
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     */
    public function setSessionAuthenticationStrategy(SessionAuthenticationStrategyInterface $sessionStrategy)
    {
        $this->sessionStrategy = $sessionStrategy;
    }

    /**
     * Clears a PreAuthenticatedToken for this provider (if present).
     * @param AuthenticationException $exception
     */
    private function clearToken(AuthenticationException $exception)
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof PreAuthenticatedToken && $this->providerKey === $token->getProviderKey()) {
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info('Cleared security token due to an exception.', ['exception' => $exception]);
            }
        }
    }

    /**
     * Gets the user and credentials from the Request.
     *
     * @param Request $request
     * @return array An array composed of the user and the credentials
     */
    abstract protected function getPreAuthenticatedData(Request $request);

    private function migrateSession(Request $request, TokenInterface $token)
    {
        if (!$this->sessionStrategy || null === $request->getAttribute('session')) {
            return;
        }

        $this->sessionStrategy->onAuthentication($request, $token);
    }
}
