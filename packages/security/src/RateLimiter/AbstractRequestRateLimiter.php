<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Security\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * An implementation of RequestRateLimiterInterface that fits most use-cases.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class AbstractRequestRateLimiter
{
    public function consume(ServerRequestInterface $request): RateLimit
    {
        $limiters = $this->getLimiters($request);
        if (0 === \count($limiters)) {
            $limiters = [new NoLimiter()];
        }

        $minimalRateLimit = null;
        foreach ($limiters as $limiter) {
            $rateLimit = $limiter->consume(1);

            if (null === $minimalRateLimit || $rateLimit->getRemainingTokens() < $minimalRateLimit->getRemainingTokens()) {
                $minimalRateLimit = $rateLimit;
            }
        }

        return $minimalRateLimit;
    }

    public function reset(ServerRequestInterface $request): void
    {
        foreach ($this->getLimiters($request) as $limiter) {
            $limiter->reset();
        }
    }

    /**
     * @return array<int,LimiterInterface> a set of limiters using keys extracted from the request
     */
    abstract protected function getLimiters(ServerRequestInterface $request): array;
}
