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

use BiuradPHP\Security\Concerns\ErrorResponse;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * BasicAuthenticationListener implements Basic HTTP authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class BasicAuthenticationListener extends AbstractListener
{
    private $tokenStorage;
    private $authenticationManager;
    private $providerKey;
    private $realmName;
    private $logger;
    private $ignoreFailure;
    private $sessionStrategy;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, string $providerKey, string $realmName, LoggerInterface $logger = null)
    {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->realmName = $realmName;
        $this->logger = $logger;
        $this->ignoreFailure = false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        $supports = '' !== $event->getRequest()->getHeaderLine('PHP_AUTH_USER');

        if ($event->getRequest()->hasHeader('Authorization')) {
            return true;
        }

        return (bool) $supports;
    }

    /**
     * Handles basic authentication.
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (null === $userIdentity = $this->parseAuthorizationHeader($request->getHeaderLine('Authorization'))) {
            if (!$request->hasHeader('PHP_AUTH_USER')) {
                return;
            }

            $userIdentity = [$request->getHeaderLine('PHP_AUTH_USER'), $request->getHeaderLine('PHP_AUTH_PW')];
        }

        [$username, $password] = $userIdentity;
        if (null !== $token = $this->tokenStorage->getToken()) {
            if (
                $token instanceof UsernamePasswordToken && $token->isAuthenticated() &&
                ($token->getUsername() === $username || $token->getUser()->getEmail() === $username)
            ) {
                return;
            }
        }

        if (null !== $this->logger) {
            $this->logger->info('Basic authentication Authorization header found for user.', ['username' => $username]);
        }

        try {
            $token = $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));

            $this->migrateSession($request, $token);

            $this->tokenStorage->setToken($token);
            $event->setAuthenticationToken($token);
        } catch (AuthenticationException $e) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey()) {
                $this->tokenStorage->setToken(null);
            }

            if (null !== $this->logger) {
                $this->logger->info('Basic authentication failed for user.', ['username' => $username, 'exception' => $e]);
            }

            if ($this->ignoreFailure) {
                return;
            }

            $response = new ErrorResponse(401, ['WWW-Authenticate' => sprintf('Basic realm="%s"', $this->realmName)]);
            $event->setResponse($response);
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
     * @param string $header
     * @return mixed[]|null
     */
	protected function parseAuthorizationHeader(string $header): ?array
	{
		if (false === strpos($header, 'Basic')) {
			return null;
        }

		$header = explode(':', (string) base64_decode(substr($header, 6), true), 2);
		return [
			$header[0],
			$header[1] ?? '',
		];
	}

    private function migrateSession(Request $request, TokenInterface $token)
    {
        if (!$this->sessionStrategy || null === $request->getAttribute('session')) {
            return;
        }

        $this->sessionStrategy->onAuthentication($request, $token);
    }
}
