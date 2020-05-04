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

namespace BiuradPHP\Security\Interfaces;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This interface must be implemented by firewall maps.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface FirewallMapInterface
{
    /**
     * Returns the authentication listeners, and the exception listener to use
     * for the given request.
     *
     * If there are no authentication listeners, the first inner array must be
     * empty.
     *
     * If there is no exception listener, the second element of the outer array
     * must be null.
     *
     * If there is no logout listener, the third element of the outer array
     * must be null.
     *
     * @param Request $request
     * @return array of the format [[AuthenticationListener], ExceptionListener, LogoutListener]
     */
    public function getListeners(Request $request);
}
