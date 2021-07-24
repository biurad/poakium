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

namespace Biurad\Http;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response.
 */
class Response implements ResponseInterface, StatusCodeInterface, \Stringable
{
    use Traits\ResponseDecoratorTrait {
        getResponse as private;
    }

    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param resource|StreamInterface|string|null $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase (optional)
     */
    public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
    {
        $this->message = new Psr7Response($status, $headers, $body, $version, $reason);
    }

    /**
     * Convert response to string.
     *
     * Note: This method is not part of the PSR-7 standard.
     */
    public function __toString(): string
    {
        $eol = "\r\n"; // EOL characters used for HTTP response
        $output = \sprintf('HTTP/%s %d %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getReasonPhrase() . $eol);

        foreach ($this->getHeaders() as $name => $values) {
            if (\count($values) > 10) {
                $output .= \sprintf('%s: %s', $name, $this->getHeaderLine($name)) . $eol;
            } else {
                foreach ($values as $value) {
                    $output .= $name . ': ' . $value . $eol;
                }
            }
        }

        return $output .= $eol . (string) $this->getBody();
    }
}
