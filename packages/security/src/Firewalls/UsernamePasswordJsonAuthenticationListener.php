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
use BiuradPHP\Http\Exceptions\ClientExceptions\BadRequestException;
use BiuradPHP\Http\Response\RedirectResponse;
use BiuradPHP\Security\Concerns\ErrorResponse;
use BiuradPHP\Security\Concerns\TargetPathTrait;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Event\InteractiveLoginEvent;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use Exception;
use function is_string;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use function strlen;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;

/**
 * UsernamePasswordJsonAuthenticationListener is a stateless implementation of
 * an authentication via a JSON document composed of a username and a password.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class UsernamePasswordJsonAuthenticationListener extends AbstractListener
{
    use TargetPathTrait;

    private $tokenStorage;
    private $authenticationManager;
    private $providerKey;
    private $options;
    private $logger;
    private $eventDispatcher;
    private $sessionStrategy;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, string $providerKey, array $options = [], LoggerInterface $logger = null, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = array_merge(['username_path' => 'username', 'password_path' => 'password'], $options);
    }

    public function supports(RequestEvent $event): ?bool
    {
        $request = $event->getRequest();
        if (
            false === (false === strpos(strtolower($request->getHeaderLine('X-Requested-With')), 'xmlhttprequest') ||
            false === strpos($request->getContentType() ?? '', 'application/json'))
        ) {
            return false;
        }

        if (isset($this->options['check_path']) && !$this->checkRequestPath($request, $this->options['check_path'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();
        $data    = $request->getParsedBody();

        try {
            $exception = new BadRequestException();
            $data = $this->getTokenForRequest($request, count($data) > 2 ? array_slice($data, 0, 2) : $data);

            try {
                $username = $data[$this->options['username_path']];
            } catch (Exception $e) {
                $exception->withMessage(sprintf('The key "%s" must be provided.', $this->options['username_path']));
                $exception->withPreviousException($e);

                throw $exception;
            }

            try {
                $password = $data[$this->options['password_path']];
            } catch (Exception $e) {
                $exception->withMessage(sprintf('The key "%s" must be provided.', $this->options['password_path']));
                $exception->withPreviousException($e);

                throw $exception;
            }

            if (!is_string($username)) {
                $exception->withMessage(sprintf('The key "%s" must be a string.', $this->options['username_path']));

                throw $exception;
            }

            if (strlen($username) > Security::MAX_USERNAME_LENGTH) {
                throw new BadCredentialsException('Invalid username.');
            }

            if (!is_string($password)) {
                $exception->withMessage(sprintf('The key "%s" must be a string.', $this->options['password_path']));

                throw $exception;
            }

            $token = new UsernamePasswordToken($username, $password, $this->providerKey);

            $authenticatedToken = $this->authenticationManager->authenticate($token);
            $response = $this->onSuccess($request, $authenticatedToken);

            if (null !== $token = $this->tokenStorage->getToken()) {
                $event->setAuthenticationToken($token);
            }
        } catch (AuthenticationException $e) {
            $response = $this->onFailure($request, $e);
        } catch (BadRequestException $e) {
            $event->setRequest($request->withContentType('application/json'));

            throw $e;
        }

        if (null === $response) {
            return;
        }

        $event->setResponse($response);
    }

    private function onSuccess(Request $request, TokenInterface $token): ?Response
    {
        if (null !== $this->logger) {
            $this->logger->info('User has been authenticated successfully.', ['username' => $token->getUsername()]);
        }

        $this->migrateSession($request, $token);

        $this->tokenStorage->setToken($token);

        if (null !== $this->eventDispatcher) {
            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($loginEvent);
        }

        $response = new RedirectResponse($this->determineTargetUrl($request), 301);

        if (!$response instanceof Response) {
            throw new RuntimeException('Authentication Success Handler did not return a Response.');
        }

        return $response;
    }

    private function onFailure(Request $request, AuthenticationException $failed): Response
    {
        if (null !== $this->logger) {
            $this->logger->info('Authentication request failed.', ['exception' => $failed]);
        }

        $token = $this->tokenStorage->getToken();
        if ($token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey()) {
            $this->tokenStorage->setToken(null);
        }

        $inputs = $request->getParsedBody();
        if (
            isset($this->options['failure_path_parameter']) &&
            $failureUrl = $inputs[$this->options['failure_path_parameter']]
        ) {
            $this->options['failure_path'] = $failureUrl;
        } elseif (null === $this->options['failure_path']) {
            if (null !== $this->logger) {
                $this->logger->debug('Authentication rebooted, redirect triggered.', ['failure_path' => $this->options['check_path']]);
            }

            return new ErrorResponse();
        }

        $response = new RedirectResponse($this->options['failure_path']);

        if (!$response instanceof Response) {
            throw new RuntimeException('Authentication Failure Handler did not return a Response.');
        }

        if (null !== $this->logger) {
            $this->logger->debug('Authentication failure, redirect triggered.', ['failure_path' => $this->options['failure_path']]);
        }

        return $response;
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
     * Builds the target URL according to the defined options.
     *
     * @param Request $request
     * @return string
     */
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        $inputs = $request->getParsedBody();
        if (
            isset($inputs[$this->options['target_path_parameter']]) &&
            $targetUrl = $inputs[$this->options['target_path_parameter']]
        ) {
            return $targetUrl;
        }

        if (null !== $this->providerKey && $targetUrl = $this->getTargetPath($request->getAttribute('session'), $this->providerKey)) {
            $this->removeTargetPath($request->getAttribute('session'), $this->providerKey);

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->getHeaderLine('Referer')) {
            if (false !== $pos = strpos($targetUrl, '?')) {
                $targetUrl = substr($targetUrl, 0, $pos);
            }
            if ($targetUrl && $targetUrl !== $request->getAttribute('router')->generateUri($this->options['check_path'])) {
                return $targetUrl;
            }
        }

        return $this->options['default_target_path'];
    }

    /**
     * Get the token for the current request.
     * @param Request $request
     * @param array $details
     * @return array|object|null
     */
    private function getTokenForRequest(Request $request, array $details)
    {
        $token = base64_encode(implode(':', $details));
        $data  = $request->getParsedBody();

        if (null !== $request->getAuthorizationToken()) {
            $token = $request->getAuthorizationToken();
        }

        if ($request->hasHeader('X-Auth-Token')) {
            $token = $request->getHeaderLine('X-Auth-Token');
        }

        if (!empty($token)) {
            [$data[$this->options['username_path']], $data[$this->options['password_path']]] = explode(':', base64_decode($token));
        }

        return $data;
    }

    private function migrateSession(Request $request, TokenInterface $token)
    {
        if (!$this->sessionStrategy || !$request->getAttribute('session') || !$request->hasCookie($request->getAttribute('session')->getName())) {
            return;
        }

        $this->sessionStrategy->onAuthentication($request, $token);
    }
}
