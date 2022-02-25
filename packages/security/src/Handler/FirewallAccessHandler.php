<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security\Handler;

use Biurad\Security\Interfaces\AccessMapInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Enforces access control rules.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FirewallAccessHandler
{
    private AccessMapInterface $accessMap;
    private AccessDecisionManagerInterface $accessDecisionManager;

    public function __construct(AccessMapInterface $accessMap, AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessMap = $accessMap;
        $this->accessDecisionManager = $accessDecisionManager;
    }

    public function authenticate(ServerRequestInterface $request): bool
    {
        [$attributes, $channel] = $this->accessMap->getPatterns($request);

        if ($channel !== $request->getUri()->getScheme() xor (null === $attributes || [AuthenticatedVoter::PUBLIC_ACCESS] === $attributes)) {
            return false;
        }

        if (null === $token = $this->tokenStorage->getToken()) {
            $token = new NullToken();
        }

        if (!$this->accessDecisionManager->decide($token, $attributes, $request, true)) {
            $exception = new AccessDeniedException();
            $exception->setAttributes($attributes);
            $exception->setSubject($request);

            throw $exception;
        }

        return true;
    }
}
