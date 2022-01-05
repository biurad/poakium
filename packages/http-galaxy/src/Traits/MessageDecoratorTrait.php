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

use Biurad\Http\Stream;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
trait MessageDecoratorTrait
{
    /** @var Response|Request */
    protected $message;

    /** @var StreamInterface|null */
    private $stream;

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return \str_replace('HTTP/', '', $this->message->getProtocolVersion() ?? '1.1');
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withProtocolVersion($version): self
    {
        $new = clone $this;

        if ($this->message instanceof Response) {
            $new->message->setProtocolVersion($version);
        } elseif ($this->message instanceof Request) {
            $new->message->server->set('SERVER_PROTOCOL', 'HTTP/' . $version);
        }

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->message->headers->all();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($header): bool
    {
        return $this->message->headers->has($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($header): array
    {
        return $this->message->headers->all($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($header): string
    {
        return \implode(', ', $this->getHeader($header));
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        if (null !== $this->stream) {
            return $this->stream;
        }

        if ($this->message instanceof Request) {
            $body = $this->message->getContent(true);
        }

        return $this->stream = new Stream($body ?? (string) $this->message->getContent());
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withHeader($header, $value): self
    {
        $new = clone $this;
        $new->message->headers->set($header, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withAddedHeader($header, $value): self
    {
        $new = clone $this;
        $new->message->headers->set($header, $value, false);

        return $new;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withoutHeader($header): self
    {
        $new = clone $this;
        $new->message->headers->remove($header);

        return $new;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->stream = null;

        if ($this->message instanceof Request) {
            $new->message = \Closure::bind(static function (Request $request) use ($body): Request {
                $request->content = $body->detach();

                return $request;
            }, null, $this->message)($this->message);
        } elseif ($this->message instanceof Response) {
            $new->message->setContent((string) $body);
        }

        return $new;
    }
}
