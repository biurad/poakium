<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Csp;

/**
 * Generates Content-Security-Policy nonce.
 *
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @internal
 */
class NonceGenerator
{
    public function generate()
    {
        return bin2hex(random_bytes(16));
    }
}
