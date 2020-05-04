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
use BiuradPHP\Security\Interfaces\RememberMeServicesInterface;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use BiuradPHP\Security\Session\SessionAuthenticationStrategy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * RememberMeListener implements authentication capabilities via a cookie.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * @final
 */
class RememberMeListener extends AbstractListener
{
    private $tokenStorage;
    private $rememberMeServices;
    private $authenticationManager;
    private $logger;
    private $dispatcher;
    private $catchExceptions = true;
    private $sessionStrategy;

    public function __construct(TokenStorageInterface $tokenStorage, RememberMeServicesInterface $rememberMeServices, AuthenticationManagerInterface $authenticationManager, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, bool $catchExceptions = true, SessionAuthenticationStrategyInterface $sessionStrategy = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->rememberMeServices = $rememberMeServices;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->catchExceptions = $catchExceptions;
        $this->sessionStrategy = null === $sessionStrategy ? new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE) : $sessionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        return null; // always run authenticate() lazily with lazy firewalls
    }

    /**
     * Handles remember-me cookie based authentication.
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        if (null !== $this->tokenStorage->getToken()) {
            return;
        }

        $request = $event->getRequest();
        try {
            if (null === $token = $this->rememberMeServices->autoLogin($request)) {
                return;
            }
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    'The token storage was not populated with remember-me token as the'
                   .' RememberMeServices was not able to create a token from the remember'
                   .' me information.', ['exception' => $e]
                );
            }

            $this->rememberMeServices->loginFail($request);

            if (!$this->catchExceptions) {
                throw $e;
            }

            return;
        }

        try {
            $token = $this->authenticationManager->authenticate($token);
            if (null !== $request->getAttribute('session') && $request->getAttribute('session')->isStarted()) {
                $this->sessionStrategy->onAuthentication($request, $token);
            }
            $this->tokenStorage->setToken($token);
            $event->setAuthenticationToken($token);

            if (null !== $this->dispatcher) {
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->dispatcher->dispatch($loginEvent);
            }

            if (null !== $this->logger) {
                $this->logger->debug('Populated the token storage with a remember-me token.');
            }
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    'The token storage was not populated with remember-me token as the'
                   .' AuthenticationManager rejected the AuthenticationToken returned'
                   .' by the RememberMeServices.', ['exception' => $e]
                );
            }

            $this->rememberMeServices->loginFail($request, $e);

            if (!$this->catchExceptions) {
                throw $e;
            }
        }
    }
}
