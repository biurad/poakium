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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This is implemented by authenticators that support token authentication.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface RequireTokenInterface
{
    /**
     * Set a previously created token if exists.
     */
    public function setToken(?TokenInterface $token): void;
}
