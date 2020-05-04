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
use BiuradPHP\MVC\Events\ExceptionEvent;
use BiuradPHP\Security\Concerns\TargetPathTrait;
use BiuradPHP\MVC\KernelEvents;
use BiuradPHP\Security\Concerns\ErrorResponse;
use BiuradPHP\Security\Exceptions\LazyResponseException;
use BiuradPHP\Security\Interfaces\AccessDeniedHandlerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface as Request, ResponseInterface as Response};
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Core\Security;
use Throwable;

/**
 * ExceptionListener catches authentication exception and converts them to
 * Response instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class ExceptionListener
{
    use TargetPathTrait;

    private $tokenStorage;
    private $providerKey;
    private $accessDeniedHandler;
    private $authenticationTrustResolver;
    private $errorPage;
    private $logger;
    private $stateless;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationTrustResolverInterface $trustResolver, AccessDeniedHandlerInterface $accessDeniedHandler = null, string $providerKey, string $errorPage = null, LoggerInterface $logger = null, bool $stateless = false)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessDeniedHandler = $accessDeniedHandler;
        $this->providerKey = $providerKey;
        $this->authenticationTrustResolver = $trustResolver;
        $this->errorPage = $errorPage;
        $this->logger = $logger;
        $this->stateless = $stateless;
    }

    /**
     * Registers a onKernelException listener to take care of security exceptions.
     * @param EventDispatcherInterface $dispatcher
     */
    public function register(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(KernelEvents::EXCEPTION, [$this, 'onKernelException'], 2);
    }

    /**
     * Handles security related exceptions.
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        do {
            if ($exception instanceof AuthenticationException) {
                $this->handleAuthenticationException($event, $exception);

                return;
            }

            if ($exception instanceof AccessDeniedException) {
                $this->handleAccessDeniedException($event, $exception);

                return;
            }

            if ($exception instanceof LazyResponseException) {
                $event->setResponse($exception->getResponse());

                return;
            }

            if ($exception instanceof LogoutException) {
                $this->handleLogoutException($exception);

                return;
            }
        } while (null !== $exception = $exception->getPrevious());
    }

    private function handleAuthenticationException(ExceptionEvent $event, AuthenticationException $exception): void
    {
        if (null !== $this->logger) {
            $this->logger->info('An AuthenticationException was thrown; redirecting to authentication entry point.', ['exception' => $exception]);
        }

        try {
            $event->setResponse($this->startAuthentication($event->getRequest(), $exception));
            $event->allowCustomResponseCode();
        } catch (Exception $e) {
            $event->setThrowable($e);
        }
    }

    private function handleAccessDeniedException(ExceptionEvent $event, AccessDeniedException $exception)
    {
        $event->setThrowable(new AccessDeniedException($exception->getMessage(), $exception));

        $token = $this->tokenStorage->getToken();
        if (!$this->authenticationTrustResolver->isFullFledged($token)) {
            if (null !== $this->logger) {
                $this->logger->debug('Access denied, the user is not fully authenticated; redirecting to authentication entry point.', ['exception' => $exception]);
            }

            try {
                $insufficientAuthenticationException = new InsufficientAuthenticationException('Full authentication is required to access this resource.', 0, $exception);
                $insufficientAuthenticationException->setToken($token);

                $event->setResponse($this->startAuthentication($event->getRequest(), $insufficientAuthenticationException));
            } catch (Exception $e) {
                $event->setThrowable($e);
            }

            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Access denied, the user is neither anonymous, nor remember-me.', ['exception' => $exception]);
        }

        try {
            if (null !== $this->accessDeniedHandler) {
                $response = $this->accessDeniedHandler->handle($event->getRequest(), $exception);

                if ($response instanceof Response) {
                    $event->setResponse($response);
                }
            } elseif (null !== $this->errorPage) {
                $subRequest = $event->getRequest()
                    ->withUri($event->getRequest()->getUri()->withPath($this->errorPage))
                    ->withAttribute(Security::ACCESS_DENIED_ERROR, $exception);

                if ($event->getKernel()->processRequest($subRequest) instanceof ResponseInterface) {
                    $event->setResponse($event->getKernel()->processRequest($subRequest));
                }
                $event->allowCustomResponseCode();
            }
        } catch (Throwable $e) {
            if (null !== $this->logger) {
                $this->logger->error('An exception was thrown when handling an AccessDeniedException.', ['exception' => $e]);
            }

            $event->setThrowable(new RuntimeException('Exception thrown when handling an exception.', 0, $e));
        }
    }

    private function handleLogoutException(LogoutException $exception): void
    {
        if (null !== $this->logger) {
            $this->logger->info('A LogoutException was thrown.', ['exception' => $exception]);
        }
    }

    private function startAuthentication(Request $request, AuthenticationException $authException): Response
    {
        if (null !== $this->logger) {
            $this->logger->debug('Calling Authentication entry point.');
        }

        if (!$this->stateless) {
            $this->setTargetPath($request);
        }

        if ($authException instanceof AccountStatusException) {
            // remove the security token to prevent infinite redirect loops
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info('The security token was removed due to an AccountStatusException.', ['exception' => $authException]);
            }
        }

        return new ErrorResponse();
    }

    protected function setTargetPath(Request $request)
    {
        // session isn't required when using HTTP basic authentication mechanism for example
        if (null !== $request->getAttribute('session') && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($request->getAttribute('session'), $this->providerKey, $request->getUri()->getPath());
        }
    }
}
