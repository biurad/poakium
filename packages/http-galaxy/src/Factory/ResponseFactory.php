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

namespace BiuradPHP\Http\Factory;

use BiuradPHP\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (false !== empty($reasonPhrase)) {
            // This will make the Response class to use a custom reasonPhrase
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
