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
use Psr\Http\Message\UriInterface;

/**
 * HTTP Request exception.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RequestException extends \RuntimeException
{
    private RequestInterface $request;

    public function __construct(string $message, RequestInterface $request, \Throwable $previous = null)
    {
        $this->request = $request;

        // Set the code of the exception if the response is set and not future.
        if ($this instanceof BadResponseException) {
            $code = $this->getResponse()->getStatusCode();
        } elseif (null !== $previous && $previous->getCode() > 0) {
            $code = $previous->getCode();
        }

        parent::__construct($message, $code ?? 500, $previous);
    }

    /**
     * Factory method to create a new exception with a normalized error message.
     *
     * @param RequestInterface  $request  Request sent
     * @param ResponseInterface $response Response received
     * @param \Throwable|null   $previous Previous exception
     */
    public static function create(RequestInterface $request, ResponseInterface $response = null, \Throwable $previous = null): self
    {
        if (null === $response) {
            return new self('Error completing request'.(null !== $previous ? ': '.$previous->getMessage() : '.'), $request, $previous);
        }

        $level = (int) \floor($response->getStatusCode() / 100);

        if (4 === $level) {
            $label = 'Client error';
            $className = ClientException::class;
        } elseif (5 === $level) {
            $label = 'Server error';
            $className = ServerException::class;
        }

        $uri = static::obfuscateUri($request->getUri());

        // Client Error: `GET /` resulted in a `404 Not Found` response:
        // <html> ... (truncated)
        $message = \sprintf(
            '%s: `%s %s` resulted in a `%s %s` response',
            $label ?? 'Unsuccessful request',
            $request->getMethod(),
            $uri,
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        if (!isset($className)) {
            return new static($message, $request, $previous);
        }

        return new $className($message, $request, $response, $previous);
    }

    /**
     * Obfuscates URI if there is a username and a password present.
     */
    private static function obfuscateUri(UriInterface $uri): UriInterface
    {
        $userInfo = $uri->getUserInfo();

        if (false !== ($pos = \strpos($userInfo, ':'))) {
            return $uri->withUserInfo(\substr($userInfo, 0, $pos), '***');
        }

        return $uri;
    }

    /**
     * Get the request that caused the exception.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
