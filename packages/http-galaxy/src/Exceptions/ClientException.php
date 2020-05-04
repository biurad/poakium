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

namespace BiuradPHP\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use RuntimeException;

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
     * @param Throwable|null $previous
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
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return void
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
