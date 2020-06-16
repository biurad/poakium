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

namespace BiuradPHP\Http\Exceptions;

use BiuradPHP\Http\Interfaces\RequestExceptionInterface;
use DomainException;
use UnexpectedValueException;

/**
 * General HttpDispatcher exception.
 */
class HttpException extends DomainException implements RequestExceptionInterface
{
    protected static $phrases = [
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden Access',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        // SERVER ERROR
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    private $customPrevious;

    /**
     * Create and returns a new instance
     *
     * @param int            $code     A valid http error code
     * @param array          $context
     * @param null|Throwable $previous
     */
    public function __construct($message = '', $code = 0, ?\Throwable $previous = null)
    {
        if (!isset(self::$phrases[$code])) {
            throw new UnexpectedValueException("Http error - Not valid ({$code})");
        }

        // Add data context used in the error handler
        return parent::__construct(
            !empty($this->message) ? $this->message : $message,
            $code,
            $this->customPrevious ?? $previous
        );
    }

    /**
     * Writes a new message for exception.
     *
     * @param string $message
     */
    public function withMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Writes a new previous exception.
     *
     * @param \Throwable $previous
     */
    public function withPreviousException(\Throwable $previous): void
    {
        $this->customPrevious = $previous;
    }
}
