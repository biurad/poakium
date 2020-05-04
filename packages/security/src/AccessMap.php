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

namespace BiuradPHP\Security;

use BiuradPHP\Http\Interfaces\RequestMatcherInterface;
use BiuradPHP\Security\Interfaces\AccessMapInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AccessMap allows configuration of different access control rules for
 * specific parts of the website.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AccessMap implements AccessMapInterface
{
    private $map = [];

    /**
     * @param RequestMatcherInterface $requestMatcher
     * @param array $attributes An array of attributes to pass to the access decision manager (like roles)
     * @param string|null $channel The channel to enforce (http, https, or null)
     */
    public function add(RequestMatcherInterface $requestMatcher, array $attributes = [], string $channel = null)
    {
        $this->map[] = [$requestMatcher, $attributes, $channel];
    }

    /**
     * {@inheritdoc}
     */
    public function getPatterns(Request $request)
    {
        foreach ($this->map as $elements) {
            if (null === $elements[0] || $elements[0]->matches($request)) {
                return [$elements[1], $elements[2]];
            }
        }

        return [null, null];
    }
}
