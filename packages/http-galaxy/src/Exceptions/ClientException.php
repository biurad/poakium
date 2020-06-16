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

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Generic client driven http exception.
 */
class ClientException extends HttpException
{
    /**
     * @var int
     */
    protected $code = 500;

    /**
     * The underlying response instance.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Create and returns a new instance
     *
     * @param int            $code     A valid http error code
     * @param array          $context
     * @param null|Throwable $previous
     */
    public function __construct(int $code = 500, Throwable $previous = null)
    {
        if (!isset(self::$phrases[$code])) {
            throw new RuntimeException("Http error not valid ({$code})");
        }

        // Add data context used in the error handler
        return parent::__construct(self::$phrases[$code], $code, $previous);
    }

    /**
     * Create a new HTTP response exception instance.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public static function withResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
