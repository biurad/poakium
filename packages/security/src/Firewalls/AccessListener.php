<?php /** @noinspection PhpUndefinedVariableInspection */

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

use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Event\LazyResponseEvent;
use BiuradPHP\Security\Interfaces\AccessMapInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * AccessListener enforces access control rules.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class AccessListener extends AbstractListener
{
    private $tokenStorage;
    private $accessDecisionManager;
    private $map;
    private $authManager;

    public function __construct(TokenStorageInterface $tokenStorage, AccessDecisionManagerInterface $accessDecisionManager, AccessMapInterface $map, AuthenticationManagerInterface $authManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->map = $map;
        $this->authManager = $authManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(RequestEvent $event): ?bool
    {
        $request = $event->getRequest();

        [$attributes] = $this->map->getPatterns($request);
        $event->setRequest($request->withAttribute('_access_control_attributes', $attributes));

        return $attributes && [AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY] !== $attributes ? true : null;
    }

    /**
     * Handles access authorization.
     *
     * @param RequestEvent $event
     */
    public function authenticate(RequestEvent $event)
    {
        if (!$event instanceof LazyResponseEvent && null === $token = $this->tokenStorage->getToken()) {
            throw new AuthenticationCredentialsNotFoundException('A Token was not found in the TokenStorage.');
        }

        $request = $event->getRequest();

        $attributes = $request->getAttribute('_access_control_attributes');
        $request = $request->withoutAttribute('_access_control_attributes');

        if (!$attributes || ([AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY] === $attributes && $event instanceof LazyResponseEvent)) {
            return;
        }

        if ($event instanceof LazyResponseEvent && null === $token = $this->tokenStorage->getToken()) {
            throw new AuthenticationCredentialsNotFoundException('A Token was not found in the TokenStorage.');
        }

        if (!$token->isAuthenticated()) {
            $token = $this->authManager->authenticate($token);
            $this->tokenStorage->setToken($token);
            $event->setAuthenticationToken($token);
        }

        $granted = false;
        foreach ($attributes as $key => $value) {
            if ($this->accessDecisionManager->decide($token, [$key => $value], $request)) {
                $granted = true;
                break;
            }
        }

        if (!$granted) {
            $exception = new AccessDeniedException();
            $exception->setAttributes($attributes);
            $exception->setSubject($request);

            throw $exception;
        }

        $event->setRequest($request);
    }
}
