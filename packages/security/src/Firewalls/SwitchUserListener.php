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

namespace BiuradPHP\Security\Firewalls;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Http\Response\RedirectResponse;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Event\SwitchUserEvent;
use BiuradPHP\Security\Middlewares\FirewallMiddleware;
use Exception;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * SwitchUserListener allows a user to impersonate another one temporarily
 * (like the Unix su command).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class SwitchUserListener extends AbstractListener
{
    const EXIT_VALUE = '_exit';

    private $tokenStorage;
    private $provider;
    private $userChecker;
    private $providerKey;
    private $accessDecisionManager;
    private $usernameParameter;
    private $role;
    private $logger;
    private $dispatcher;
    private $stateless;

    public function __construct(TokenStorageInterface $tokenStorage, UserProviderInterface $provider, UserCheckerInterface $userChecker, string $providerKey, AccessDecisionManagerInterface $accessDecisionManager, LoggerInterface $logger = null, string $usernameParameter = '_switch_user', string $role = 'ROLE_ALLOWED_TO_SWITCH', EventDispatcherInterface $dispatcher = null, bool $stateless = false)
    {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->provider = $provider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->usernameParameter = $usernameParameter;
        $this->role = $role;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->stateless = $stateless;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        $request = $event->getRequest();

        // usernames can be falsy
        $inputs = $request->getParsedBody();
        $username = isset($inputs[$this->usernameParameter]) ? $inputs[$this->usernameParameter] : null;

        if (null === $username || '' === $username) {
            $username = $request->getHeaderLine($this->usernameParameter);
        }

        // if it's still "empty", nothing to do.
        if (null === $username || '' === $username) {
            return false;
        }

        $event->setRequest($request->withAttribute('_switch_user_username', $username));

        return true;
    }

    /**
     * Handles the switch to another user.
     *
     * @param RequestEvent $event
     * @throws Exception
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();

        $username = $request->getAttribute('_switch_user_username');
        $request = $request->withoutAttribute('_switch_user_username');

        if (null === $this->tokenStorage->getToken()) {
            throw new AuthenticationCredentialsNotFoundException('Could not find original Token object.');
        }

        if (self::EXIT_VALUE === $username) {
            $this->tokenStorage->setToken($token = $this->attemptExitUser($request));
        } else {
            try {
                $this->tokenStorage->setToken($token = $this->attemptSwitchUser($request, $username));
            } catch (AuthenticationException $e) {
                // Generate 403 in any conditions to prevent user enumeration vulnerabilities
                throw new AccessDeniedException('Switch User failed: '.$e->getMessage(), $e);
            }
        }
        $event->setAuthenticationToken($token);

        if (!$this->stateless) {
            $response = new RedirectResponse($request->getUri()->getPath(), FirewallMiddleware::REDIRECT_STATUS);

            $event->setResponse($response);
        }
    }

    /**
     * Attempts to switch to another user and returns the new token if successfully switched.
     *
     * @param Request $request
     * @param string $username
     *
     * @return TokenInterface|null
     * @throws Exception
     */
    private function attemptSwitchUser(Request $request, string $username): ?TokenInterface
    {
        $token = $this->tokenStorage->getToken();
        $originalToken = $this->getOriginalToken($token);

        if (null !== $originalToken) {
            if (!filter_var($username, FILTER_VALIDATE_EMAIL) && $token->getUsername() === $username) {
                return $token;
            } elseif ($token->getUser()->getEmail() === $username) {
                return $token;
            }

            throw new LogicException(sprintf('You are already switched to "%s" user.', $username));
        }

        $currentUsername = $token->getUsername() ?? $token->getUser()->getEmail();
        $nonExistentUsername = '_'.md5(random_bytes(8).$username);

        // To protect against user enumeration via timing measurements
        // we always load both successfully and unsuccessfully
        try {
            $user = $this->provider->loadUserByUsername($username);

            try {
                $this->provider->loadUserByUsername($nonExistentUsername);
            } catch (AuthenticationException $e) {
            }
        } catch (AuthenticationException $e) {
            $this->provider->loadUserByUsername($currentUsername);

            throw $e;
        }

        if (false === $this->accessDecisionManager->decide($token, [$this->role], $user)) {
            $exception = new AccessDeniedException();
            $exception->setAttributes($this->role);

            throw $exception;
        }

        if (null !== $this->logger) {
            $this->logger->info('Attempting to switch to user.', ['username' => $username]);
        }

        $this->userChecker->checkPostAuth($user);

        $roles = $user->getRoles();
        $roles[] = 'ROLE_PREVIOUS_ADMIN';
        $token = new SwitchUserToken($user, $user->getPassword(), $this->providerKey, $roles, $token);

        if (null !== $this->dispatcher) {
            $switchEvent = new SwitchUserEvent($request, $token->getUser(), $token);
            $this->dispatcher->dispatch($switchEvent);

            // use the token from the event in case any listeners have replaced it.
            $token = $switchEvent->getToken();
        }

        return $token;
    }

    /**
     * Attempts to exit from an already switched user and returns the original token.
     *
     * @param Request $request
     *
     * @return TokenInterface
     */
    private function attemptExitUser(Request $request): TokenInterface
    {
        if (null === ($currentToken = $this->tokenStorage->getToken()) || null === $original = $this->getOriginalToken($currentToken)) {
            throw new AuthenticationCredentialsNotFoundException('Could not find original Token object.');
        }

        if (null !== $this->dispatcher && $original->getUser() instanceof UserInterface) {
            $user = $this->provider->refreshUser($original->getUser());
            $switchEvent = new SwitchUserEvent($request, $user, $original);
            $this->dispatcher->dispatch($switchEvent);
            $original = $switchEvent->getToken();
        }

        return $original;
    }

    private function getOriginalToken(TokenInterface $token): ?TokenInterface
    {
        if ($token instanceof SwitchUserToken) {
            return $token->getOriginalToken();
        }

        return null;
    }
}
