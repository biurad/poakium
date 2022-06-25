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
 * XML response with Content-Type header to application/xml.
 */
class XmlResponse extends Response
{
    /**
     * Produces an XML response with a Content-Type of application/xml and a default status of 200.
     *
     * @param StreamInterface|string $xml     string or stream for the message body
     * @param int                    $status  integer status code for the response; 200 by default
     * @param array                  $headers array of headers to use at initialization
     */
    public function __construct($xml, int $status = 200, array $headers = [])
    {
        parent::__construct($status, ['content-type' => ['application/xml; charset=utf-8']] + $headers, $xml);
    }
}
