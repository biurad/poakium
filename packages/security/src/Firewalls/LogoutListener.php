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

use BiuradPHP\Http\Response\RedirectResponse;
use BiuradPHP\Routing\RouteCollection;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Interfaces\LogoutHandlerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * LogoutListener logout users.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class LogoutListener extends AbstractListener
{
    private $tokenStorage;
    private $options;
    private $targetUrl;
    private $handlers;
    private $router;
    private $csrfTokenManager;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param array $options An array of options to process a logout attempt
     * @param string $targetUrl
     * @param CsrfTokenManagerInterface|null $csrfTokenManager
     * @param RouteCollection $router
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $options = [], string $targetUrl, CsrfTokenManagerInterface $csrfTokenManager = null, RouteCollection $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->options = array_merge([
            'csrf_parameter' => '_csrf_token',
            'csrf_token_id' => 'logout',
            'logout_path' => '/logout',
        ], $options);
        $this->targetUrl = $targetUrl;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->handlers = [];
    }

    public function addHandler(LogoutHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        return $this->requiresLogout($event->getRequest());
    }

    /**
     * Performs the logout if requested.
     *
     * If a CsrfTokenManagerInterface instance is available, it will be used to
     * validate the request.
     *
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (null !== $this->csrfTokenManager) {
            $inputs = $request->getParsedBody();
            $csrfToken = isset($inputs[$this->options['csrf_parameter']]) ? $inputs[$this->options['csrf_parameter']] : null;

            if (false !== $this->options['csrf_status']) {
                if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['csrf_token_id'], $csrfToken))) {
                    throw new LogoutException('Invalid CSRF token.');
                }
            }
        }

        // Default logout success handler will redirect users to a configured path.
        $response = new RedirectResponse($this->targetUrl);

        if (!$response instanceof Response) {
            throw new RuntimeException('Logout Success Handler did not return a Response.');
        }

        // handle multiple logout attempts gracefully
        if ($token = $this->tokenStorage->getToken()) {
            /** @var LogoutHandlerInterface $handler */
            foreach ($this->handlers as $handler) {
                $handler->logout($request, $response, $token);
            }
        }

        $this->tokenStorage->setToken(null);

        $event->setResponse($response);
    }

    /**
     * Whether this request is asking for logout.
     *
     * The default implementation only processed requests to a specific path,
     * but a subclass could change this to logout requests where
     * certain parameters is present.
     * @param Request $request
     *
     * @return bool
     */
    protected function requiresLogout(Request $request): bool
    {
        return isset($this->options['logout_path']) && $this->checkRequestPath($request, $this->options['logout_path'], $this->router);
    }
}
