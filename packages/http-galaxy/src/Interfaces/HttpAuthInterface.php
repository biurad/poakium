<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Interfaces;

/**
 * A PHP callable that will authenticate the HTTP authentication information.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface HttpAuthInterface
{
    /**
     * @param array<int,string>|string $credentials
     */
    public function __invoke($credentials, string $type): bool;
}
