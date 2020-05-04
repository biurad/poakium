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

use Psr\Log\LoggerInterface;
use BiuradPHP\Routing\RouteCollection;
use Psr\Http\Message\ServerRequestInterface as Request;
use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * UsernamePasswordFormAuthenticationListener is the default implementation of
 * an authentication via a simple form composed of a username and a password.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UsernamePasswordFormAuthenticationListener extends AbstractAuthenticationListener
{
    private $csrfTokenManager;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, string $providerKey, RouteCollection $urlMatcher, array $options = [], LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, CsrfTokenManagerInterface $csrfTokenManager = null)
    {
        parent::__construct($tokenStorage, $authenticationManager, $sessionStrategy, $urlMatcher, $providerKey, array_merge([
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'csrf_parameter' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'csrf_status' => true,
            'post_only' => true,
        ], $options), $logger, $dispatcher);

        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->options['post_only'] && !$request->isMethod('POST')) {
            return false;
        }

        return parent::requiresAuthentication($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $inputs = $request->getParsedBody();
        if (null !== $this->csrfTokenManager) {
            $csrfToken = isset($inputs[$this->options['csrf_parameter']]) ? $inputs[$this->options['csrf_parameter']] : null;

            if (false !== $this->options['csrf_status']) {
                if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['csrf_token_id'], $csrfToken))) {
                    throw new InvalidCsrfTokenException('Invalid CSRF token.');
                }
            }
        }

        if ($this->options['post_only']) {
            $username = isset($inputs[$this->options['username_parameter']]) ? $inputs[$this->options['username_parameter']] : null;
            $password = isset($inputs[$this->options['password_parameter']]) ? $inputs[$this->options['password_parameter']] : null;
        } else {
            $username = isset($inputs[$this->options['username_parameter']]) ? $inputs[$this->options['username_parameter']] : null;
            $password = isset($inputs[$this->options['password_parameter']]) ? $inputs[$this->options['password_parameter']] : null;
        }

        if (!is_string($username) && (!is_object($username) || !method_exists($username, '__toString'))) {
            throw new BadCredentialsException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], gettype($username)));
        }
        $username = trim($username);

        if (strlen($username) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }
        $request->getAttribute('session')->getSection()->set(Security::LAST_USERNAME, $username);

        return $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
    }
}
