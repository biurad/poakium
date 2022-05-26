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

namespace Biurad\Security;

use Biurad\Http\ServerRequest;
use Biurad\Security\Interfaces\AccessMapInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * AccessMap allows configuration of different access control rules for
 * specific parts of the website.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AccessMap implements AccessMapInterface
{
    /** @var array<int,mixed> */
    private array $map = [];

    /**
     * @param array       $attributes An array of attributes to pass to the access decision manager (like roles)
     * @param string|null $channel    The channel to enforce (http, https, or null)
     */
    public function add(RequestMatcherInterface $requestMatcher, array $attributes = [], string $channel = null): void
    {
        $this->map[] = [$requestMatcher, $attributes, $channel];
    }

    /**
     * {@inheritdoc}
     */
    public function getPatterns(ServerRequestInterface $request): array
    {
        if (!$request instanceof ServerRequest) {
            throw new \InvalidArgumentException(\sprintf('The request must be an instance of %s.', ServerRequest::class));
        }

        if (!empty($this->map)) {
            $request = $request->getRequest();

            foreach ($this->map as [$requestMatcher, $attributes, $channel]) {
                if ($requestMatcher->matches($request)) {
                    return [$attributes, $channel];
                }
            }
        }

        return [null, null];
    }
}
