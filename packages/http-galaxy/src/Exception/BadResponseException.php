<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception when an HTTP error occurs (4xx or 5xx error).
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class BadResponseException extends RequestException
{
    private ResponseInterface $response;

    public function __construct(string $message, RequestInterface $request, ResponseInterface $response, \Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $request, $previous);
    }

    /**
     * Current exception and the ones that extend it will always have a response.
     */
    public function hasResponse(): bool
    {
        return true;
    }

    /**
     * This function narrows the return type from the parent class and does not allow it to be nullable.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
