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

namespace BiuradPHP\Http\Response;

use BiuradPHP\Http\Response;
use GuzzleHttp\Exception;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;
use BiuradPHP\Http\Traits\InjectContentTypeTrait;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

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
     * @param string|StreamInterface $xml String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream.
     */
    public function __construct(
        $xml,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            $status,
            $this->injectContentType('application/xml; charset=utf-8', $headers),
            $this->createBody($xml)
        );
    }

    /**
     * Create the message body.
     *
     * @param string|StreamInterface $xml
     * @throws Exception\InvalidArgumentException if $xml is neither a string or stream.
     */
    private function createBody($xml) : StreamInterface
    {
        if ($xml instanceof StreamInterface) {
            return $xml;
        }

        if (! is_string($xml)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                (is_object($xml) ? get_class($xml) : gettype($xml)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($xml);
        $body->rewind();
        return $body;
    }
}
