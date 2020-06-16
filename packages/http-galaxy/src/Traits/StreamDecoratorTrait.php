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

use Psr\Http\Message\StreamInterface;

trait StreamDecoratorTrait
{
    /** @var StreamInterface */
    protected $stream;

    public function __destruct()
    {
        $this->stream->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->stream->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->stream->close();
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return $this->stream->detach();
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        return $this->stream->tell();
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->stream->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->stream->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int
    {
        return $this->stream->write($string);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        return $this->stream->read($length);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        return $this->stream->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }
}
