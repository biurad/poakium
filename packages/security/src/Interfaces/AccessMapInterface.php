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

namespace Biurad\Security\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

/**
 * AccessMap allows configuration of different access control rules for
 * specific parts of the website.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface AccessMapInterface
{
    /**
     * Returns security attributes and required channel for the supplied request.
     *
     * @return array<int,mixed> A tuple of security attributes and the required channel
     */
    public function getPatterns(ServerRequestInterface $request): array;
}
