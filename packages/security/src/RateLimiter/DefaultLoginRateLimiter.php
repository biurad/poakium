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

use Biurad\Http\Request as HttpRequest;
use Biurad\Security\Helper;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * A default login throttling limiter.
 *
 * This limiter prevents breadth-first attacks by enforcing
 * a limit on username+IP and a (higher) limit on IP.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class DefaultLoginRateLimiter extends AbstractRequestRateLimiter
{
    private RateLimiterFactory $globalFactory;
    private RateLimiterFactory $localFactory;
    private string $userParameter;

    public function __construct(RateLimiterFactory $globalFactory, RateLimiterFactory $localFactory, string $userId = '_identifier')
    {
        $this->globalFactory = $globalFactory;
        $this->localFactory = $localFactory;
        $this->userParameter = $userId;
    }

    protected function getLimiters(ServerRequestInterface $request): array
    {
        if ($request instanceof HttpRequest) {
            $ip = $request->getRequest()->getClientIp();
            $username = $request->getRequest()->get($this->userParameter);
        } else {
            $username = Helper::getParameterValue($request, $this->userParameter);
        }

        $limiters = [$this->globalFactory->create($ip ?? $request->getServerParams()['REMOTE_ADDR'])];

        if (!empty($username)) {
            $username = \preg_match('//u', $username) ? \mb_strtolower($username, 'UTF-8') : \strtolower($username);
            $limiters[] = $this->localFactory->create($username . '-' . $ip ?? $request->getServerParams()['REMOTE_ADDR']);
        }

        return $limiters;
    }
}
