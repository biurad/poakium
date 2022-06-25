<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\Http\Response;

use Biurad\Http\Response;
use Psr\Http\Message\StreamInterface;

/**
 * HTML response with Content-Type header to text/html.
 */
class HtmlResponse extends Response
{
    /**
     * Produces an HTML response with a Content-Type of text/html and a default status of 200.
     *
     * @param StreamInterface|string $html    HTML or stream for the message body
     * @param int                    $status  integer status code for the response; 200 by default
     * @param array                  $headers array of headers to use at initialization
     */
    public function __construct($html, int $status = 200, array $headers = [])
    {
        parent::__construct($status, ['content-type' => ['text/html; charset=utf-8']] + $headers, $html);
    }
}
