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
use Biurad\Http\Traits\InjectContentTypeTrait;
use GuzzleHttp\Exception;
use Psr\Http\Message\StreamInterface;

/**
 * Plain text response.
 *
 * Allows creating a response by passing a string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/plain.
 */
class TextResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create a plain text response.
     *
     * Produces a text response with a Content-Type of text/plain and a default
     * status of 200.
     *
     * @param StreamInterface|string $text    string or stream for the message body
     * @param int                    $status  integer status code for the response; 200 by default
     * @param array                  $headers array of headers to use at initialization
     *
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream
     */
    public function __construct($text, int $status = 200, array $headers = [])
    {
        parent::__construct(
            $status,
            $this->injectContentType('text/plain; charset=utf-8', $headers),
            $text
        );
    }
}
