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

namespace BiuradPHP\Http;

use GuzzleHttp\Psr7\Stream as Psr7Stream;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    use Traits\StreamDecoratorTrait;

    public function __construct($body = '', $options = [])
    {
        $this->stream = new Psr7Stream($body, $options);
    }
}
