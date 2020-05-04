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

namespace BiuradPHP\Security\Logout;

use BiuradPHP\Routing\RouteCollection;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Provides generator functions for the logout URL.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jeremy Mikola <jmikola@gmail.com>
 */
class LogoutUrlGenerator
{
    private $request;
    private $router;
    private $tokenStorage;
    private $listeners = [];
    private $currentFirewall;

    /**
     * Generates an absolute URL, e.g. "http://example.com/dir/file".
     */
    const ABSOLUTE_URL = 0;

    /**
     * Generates an absolute path, e.g. "/dir/file".
     */
    const ABSOLUTE_PATH = 1;

    public function __construct(ServerRequestInterface $request, RouteCollection $router = null, TokenStorageInterface $tokenStorage = null)
    {
        $this->request = $request;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Registers a firewall's LogoutListener, allowing its URL to be generated.
     *
     * @param string $key The firewall key
     * @param string $logoutPath The path that starts the logout process
     * @param string|null $csrfTokenId The ID of the CSRF token
     * @param string|null $csrfParameter The CSRF token parameter name
     * @param CsrfTokenManagerInterface|null $csrfTokenManager
     * @param string|null $context The listener context
     */
    public function registerListener(string $key, string $logoutPath, ?string $csrfTokenId, ?string $csrfParameter, CsrfTokenManagerInterface $csrfTokenManager = null, string $context = null)
    {
        $this->listeners[$key] = [$logoutPath, $csrfTokenId, $csrfParameter, $csrfTokenManager, $context];
    }

    /**
     * Generates the absolute logout path for the firewall.
     *
     * @param string|null $key
     * @return string The logout path
     */
    public function getLogoutPath(string $key = null)
    {
        return $this->generateLogoutUrl($key, self::ABSOLUTE_PATH);
    }

    /**
     * Generates the absolute logout URL for the firewall.
     *
     * @param string|null $key
     * @return string The logout URL
     */
    public function getLogoutUrl(string $key = null)
    {
        return $this->generateLogoutUrl($key, self::ABSOLUTE_URL);
    }

    public function setCurrentFirewall(?string $key, string $context = null)
    {
        $this->currentFirewall = [$key, $context];
    }

    /**
     * Generates the logout URL for the firewall.
     *
     * @param string|null $key
     * @param int $referenceType
     *
     * @return string The logout URL
     */
    private function generateLogoutUrl(?string $key, int $referenceType): string
    {
        [$logoutPath, $csrfTokenId, $csrfParameter, $csrfTokenManager] = $this->getListener($key);

        if (null === $logoutPath) {
            throw new LogicException('Unable to generate the logout URL without a path.');
        }

        $parameters = null !== $csrfTokenManager ? [$csrfParameter => (string) $csrfTokenManager->getToken($csrfTokenId)] : [];

        if ('/' === $logoutPath[0]) {

            $request = $this->request;
            $logoutPath = substr($request->getUri()->getPath(), strlen($logoutPath)). $logoutPath;

            $url = self::ABSOLUTE_URL === $referenceType ? $request->getUriForPath($logoutPath) : $logoutPath;

            if (!empty($parameters)) {
                $url .= '?'.http_build_query($parameters, '', '&');
            }
        } else {
            if (!$this->router) {
                throw new LogicException('Unable to generate the logout URL without a Router.');
            }

            $url = $this->router->generateUri($logoutPath, $parameters);
        }

        return $url;
    }

    /**
     * @param string|null $key
     * @return array
     */
    private function getListener(?string $key): array
    {
        if (null !== $key) {
            if (isset($this->listeners[$key])) {
                return $this->listeners[$key];
            }

            throw new InvalidArgumentException(sprintf('No LogoutListener found for firewall key "%s".', $key));
        }

        // Fetch the current provider key from token, if possible
        if (null !== $this->tokenStorage) {
            $token = $this->tokenStorage->getToken();

            if ($token instanceof AnonymousToken) {
                throw new InvalidArgumentException('Unable to generate a logout url for an anonymous token.');
            }

            if (null !== $token && method_exists($token, 'getProviderKey')) {
                $key = $token->getProviderKey();

                if (isset($this->listeners[$key])) {
                    return $this->listeners[$key];
                }
            }
        }

        // Fetch from injected current firewall information, if possible
        [$key, $context] = $this->currentFirewall;

        if (isset($this->listeners[$key])) {
            return $this->listeners[$key];
        }

        foreach ($this->listeners as $listener) {
            if (isset($listener[4]) && $context === $listener[4]) {
                return $listener;
            }
        }

        throw new InvalidArgumentException('Unable to find the current firewall LogoutListener, please provide the provider key manually.');
    }
}
