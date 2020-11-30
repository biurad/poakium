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

namespace Biurad\Http\Traits;

use Biurad\Http\Cookie;
use Biurad\Http\Interfaces\CookieInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

trait ServerRequestDecoratorTrait
{
    use RequestDecoratorTrait;

    /**
     * Returns the decorated request.
     *
     * Since the underlying Request is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        /** @var ServerRequestInterface $message */
        $message = $this->getMessage();

        return $message;
    }

    /**
     * Exchanges the underlying server request with another.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    public function withRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $new          = clone $this;
        $new->message = $request;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->getRequest()->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->getRequest()->getAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->getRequest()->getCookieParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->getRequest()->getParsedBody();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->getRequest()->getQueryParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->getRequest()->getServerParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->getRequest()->getUploadedFiles();
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withAttribute($name, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withoutAttribute($name);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withCookieParams($cookies);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withParsedBody($data);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withQueryParams($query);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new          = clone $this;
        $new->message = $this->getRequest()->withUploadedFiles($uploadedFiles);

        return $new;
    }

    /**
     * Fetch cookie value from cookies sent by the client to the server.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key     the attribute name
     * @param mixed  $default default value to return if the attribute does not exist
     *
     * @return CookieInterface|mixed
     */
    public function getCookie(string $key, $default = null)
    {
        $cookies = $this->getRequest()->getCookieParams();

        if (isset($cookies[$key])) {
            return Cookie::fromString($cookies[$key]);
        }

        return $default;
    }

    /**
     * Checks if a cookie exists in the browser's request
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $name The cookie name
     *
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return $this->getCookie($name) instanceof CookieInterface;
    }

    /**
     * Fetch serverRequest parameter value from server, body or query string (in that order).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key     the parameter key
     * @param mixed  $default the default value
     *
     * @return mixed the parameter value
     */
    public function getParameter(string $key, $default = null)
    {
        $request = $this->getRequest();

        $postParams = $request->getParsedBody();
        $getParams  = $request->getQueryParams();
        $result     = $default;

        if (array_key_exists($key, $serverParams = $request->getServerParams())) {
            return $serverParams[$key];
        }

        if (\is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (\is_object($postParams) && $postParams instanceof stdClass) {
            $result = $postParams->{$key};
        }

        if (null === $result && isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Generates a normalized URI for the given path,
     * Including active port on current domain.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $path A path to use instead of the current one
     *
     * @return string The normalized URI for the path
     */
    public function getUriForPath(string $path): string
    {
        $uri    = $this->getUri();
        $port   = $uri->getPort();
        $query  = $uri->getQuery();

        if ('' !== $query) {
            $query = '?' . $query;
        }

        if (null !== $uri->getPort() && !\in_array($uri->getPort(), [80, 443], true)) {
            $port = ':' . $uri->getPort();
        }

        return \sprintf('%s://%s%s', $uri->getScheme(), $uri->getAuthority(), $port . $path . $query);
    }

    /**
     * Get ip addr resolved from $_SERVER['REMOTE_ADDR']. Will return null if nothing if key not
     * exists. Consider using psr-15 middlewares to customize configuration.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return null|string
     */
    public function getRemoteAddress(): ?string
    {
        return $this->getParameter('REMOTE_ADDR');
    }
}
