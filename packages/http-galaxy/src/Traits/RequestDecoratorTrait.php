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

namespace BiuradPHP\Http\Traits;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait RequestDecoratorTrait
{
    use MessageDecoratorTrait {
        getMessage as private;
    }

    /**
     * Returns the decorated request.
     *
     * Since the underlying Request is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        /** @var RequestInterface $message */
        $message = $this->getMessage();

        return $message;
    }

    /**
     * Exchanges the underlying request with another.
     *
     * @param RequestInterface $request
     * @return self
     */
    public function withRequest(RequestInterface $request): self
    {
        $new = clone $this;
        $new->message = $request;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget(): string
    {
        return $this->getRequest()->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget): self
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withRequestTarget($requestTarget);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method): self
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withMethod($method);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->getRequest()->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new = clone $this;
        $new->message = $this->getRequest()->withUri($uri, $preserveHost);

        return $new;
    }
}
