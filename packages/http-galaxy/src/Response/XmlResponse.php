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

namespace BiuradPHP\Http\Response;

use BiuradPHP\Http\Response;
use BiuradPHP\Http\Traits\InjectContentTypeTrait;
use GuzzleHttp\Exception;
use Psr\Http\Message\StreamInterface;

/**
 * XML response.
 *
 * Allows creating a response by passing an XML string to the constructor; by default,
 * sets a status code of 200 and sets the Content-Type header to application/xml.
 */
class XmlResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create an XML response.
     *
     * Produces an XML response with a Content-Type of application/xml and a default
     * status of 200.
     *
     * @param StreamInterface|string $xml     string or stream for the message body
     * @param int                    $status  integer status code for the response; 200 by default
     * @param array                  $headers array of headers to use at initialization
     *
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream
     */
    public function __construct(
        $xml,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            $status,
            $this->injectContentType('application/xml; charset=utf-8', $headers),
            $xml
        );
    }
}
