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

namespace BiuradPHP\Http\Traits;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait MessageDecoratorTrait
{
    /** @var MessageInterface */
    private $message;

    /**
     * Returns the decorated message.
     *
     * Since the underlying Message is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->message->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version): self
    {
        $new          = clone $this;
        $new->message = $this->message->withProtocolVersion($version);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->message->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($header): bool
    {
        return $this->message->hasHeader($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($header): array
    {
        return $this->message->getHeader($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($header): string
    {
        return $this->message->getHeaderLine($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->message->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($header, $value): self
    {
        $new          = clone $this;
        $new->message = $this->message->withHeader($header, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($header, $value): self
    {
        $new          = clone $this;
        $new->message = $this->message->withAddedHeader($header, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($header): self
    {
        $new          = clone $this;
        $new->message = $this->message->withoutHeader($header);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body): self
    {
        $new          = clone $this;
        $new->message = $this->message->withBody($body);

        return $new;
    }
}
