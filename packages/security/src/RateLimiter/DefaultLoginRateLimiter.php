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

namespace Biurad\Security\RateLimiter;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Security;

/**
 * A default login throttling limiter.
 *
 * This limiter prevents breadth-first attacks by enforcing
 * a limit on username+IP and a (higher) limit on IP.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
final class DefaultLoginRateLimiter extends AbstractRequestRateLimiter
{
    private RateLimiterFactory $globalFactory;
    private RateLimiterFactory $localFactory;

    public function __construct(RateLimiterFactory $globalFactory, RateLimiterFactory $localFactory)
    {
        $this->globalFactory = $globalFactory;
        $this->localFactory = $localFactory;
    }

    protected function getLimiters(Request $request): array
    {
        $limiters = [$this->globalFactory->create($request->getClientIp())];

        if (null !== $username = $request->attributes->get(Security::LAST_USERNAME)) {
            $username = \preg_match('//u', $username) ? \mb_strtolower($username, 'UTF-8') : \strtolower($username);
            $limiters[] = $this->localFactory->create($username . '-' . $request->getClientIp());
        }

        return $limiters;
    }
}
