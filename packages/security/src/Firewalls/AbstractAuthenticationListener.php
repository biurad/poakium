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

use BiuradPHP\Http\Response as HttpResponse;
use BiuradPHP\Routing\RouteCollection;
use BiuradPHP\Security\Concerns\TargetPathTrait;
use BiuradPHP\Security\Event\InteractiveLoginEvent;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Interfaces\RememberMeServicesInterface;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use BiuradPHP\Security\Middlewares\FirewallMiddleware;
use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Security;

/**
 * The AbstractAuthenticationListener is the preferred base class for all
 * browser-/HTTP-based authentication requests.
 *
 * Subclasses likely have to implement the following:
 * - an TokenInterface to hold authentication related data
 * - an AuthenticationProvider to perform the actual authentication of the
 *   token, retrieve the UserInterface implementation from a database, and
 *   perform the specific account checks using the UserChecker
 *
 * By default, this listener only is active for a specific path, e.g.
 * /login_check. If you want to change this behavior, you can overwrite the
 * requiresAuthentication() method.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractAuthenticationListener extends AbstractListener
{
    use TargetPathTrait;

    protected $options;
    protected $logger;
    protected $authenticationManager;
    protected $providerKey;
    protected $urlMatcher;

    private $tokenStorage;
    private $sessionStrategy;
    private $dispatcher;
    private $rememberMeServices;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param RouteCollection $urlMatcher
     * @param string $providerKey
     * @param array $options
     * @param LoggerInterface|null $logger
     * @param EventDispatcherInterface|null $dispatcher
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, RouteCollection $urlMatcher, string $providerKey, array $options = [], LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->sessionStrategy = $sessionStrategy;
        $this->providerKey = $providerKey;
        $this->options = array_merge([
            'check_path' => '/login',
            'login_path' => '/login',
            'always_use_default_target_path' => false,
            'default_target_path' => './',
            'target_path_parameter' => '_target_path',
            'use_referer' => false,
            'failure_path' => null,
            'failure_forward' => false,
            'require_previous_session' => true,
        ], $options);
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * Sets the RememberMeServices implementation to use.
     * @param RememberMeServicesInterface $rememberMeServices
     */
    public function setRememberMeServices(RememberMeServicesInterface $rememberMeServices)
    {
        $this->rememberMeServices = $rememberMeServices;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        return $this->requiresAuthentication($event->getRequest());
    }

    /**
     * Handles form based authentication.
     *
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        $request  = $event->getRequest();
        $response = new HttpResponse(FirewallMiddleware::REDIRECT_STATUS, [], 'php://temp');

        if (null == $session = $request->getAttribute('session')) {
            throw new RuntimeException('This authentication method requires a session.');
        }

        try {
            if ($this->options['require_previous_session'] && ! $request->hasCookie($session->getName())) {
                throw new SessionUnavailableException('Your session has timed out, or you have disabled cookies.');
            }

            if (null === $returnValue = $this->attemptAuthentication($request)) {
                return;
            }

            if ($returnValue instanceof TokenInterface) {
                $this->sessionStrategy->onAuthentication($request, $returnValue);

                $response = $this->onSuccess($request, $response, $returnValue);
                $event->setAuthenticationToken($returnValue);
            } elseif ($returnValue instanceof Response) {
                $response = $returnValue;
            } else {
                throw new RuntimeException('attemptAuthentication() must either return a Response, an implementation of TokenInterface, or null.');
            }

            $event->setResponse($response);
        } catch (AuthenticationException $e) {
            $response = $this->onFailure($request, $response, $e);
        }

        $response; // Allowing sessions passed "attemptAuthentication" and onFailure to be active.
    }

    /**
     * Whether this request requires authentication.
     *
     * The default implementation only processes requests to a specific path,
     * but a subclass could change this to only authenticate requests where a
     * certain parameters is present.
     *
     * @param Request $request
     * @return bool
     */
    protected function requiresAuthentication(Request $request)
    {
        return $this->checkRequestPath($request, $this->options['check_path'], $this->urlMatcher);
    }

    /**
     * Performs authentication.
     *
     * @param Request $request
     * @return TokenInterface|Response|null The authenticated token, null if full authentication is not possible, or a Response
     *
     */
    abstract protected function attemptAuthentication(Request $request);

    private function onFailure(Request $request, Response $response, AuthenticationException $failed): Response
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
            $this->options['failure_path'] = $this->options['login_path'];
        }

        $request->getAttribute('session')->getSection()->set(Security::AUTHENTICATION_ERROR, $failed);

        if (!$response instanceof Response) {
            throw new RuntimeException('Authentication Failure Handler did not return a Response.');
        }

        if (null !== $this->logger) {
            $this->logger->debug('Authentication failure, redirect triggered.', ['failure_path' => $this->options['failure_path']]);
        }

        return $response->withAddedHeader('Location', $this->options['failure_path']);
    }

    private function onSuccess(Request $request, ?Response $response, TokenInterface $token): Response
    {
        if (null !== $this->logger) {
            $this->logger->info(
                'User has been authenticated successfully.',
                ['username' => $token->getUsername() ?? $token->getUser()->getEmail()]
            );
        }

        $this->tokenStorage->setToken($token);

        $session = $request->getAttribute('session');
        $session->getSection()->delete(Security::AUTHENTICATION_ERROR);
        $session->getSection()->delete(Security::LAST_USERNAME);

        if (null !== $this->dispatcher) {
            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->dispatcher->dispatch($loginEvent);

            $request = $loginEvent->getRequest();
        }

        if (!$response instanceof Response) {
            throw new RuntimeException('Authentication Success Handler did not return a Response.');
        }

        if (null !== $this->rememberMeServices) {
            $this->rememberMeServices->loginSuccess($request, $token);
        }

        return $response->withAddedHeader('Location', $this->determineTargetUrl($request));
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
            if ($targetUrl && $targetUrl !== $this->urlMatcher->generateUri($this->options['login_path'])) {
                return $targetUrl;
            }
        }

        return $this->options['default_target_path'];
    }
}
