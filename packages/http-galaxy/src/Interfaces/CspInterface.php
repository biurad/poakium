<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Http\Interfaces;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface CspInterface
{
    /**
     * Cleanup temporary headers and updates Content-Security-Policy headers.
     *
     * @return array Nonces used by the bundle in Content-Security-Policy header
     */
    public function updateResponseHeaders(Request $request, Response &$response);
}
