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

namespace Biurad\Http;

use Biurad\Http\Exception\InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Describes a data stream.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Stream implements StreamInterface
{
    /** @var bool|null */
    private $resource;

    /** @var bool|null */
    private $size;

    /** @var bool|null */
    private $seekable;

    /** @var bool|null */
    private $writable;

    /** @var bool|null */
    private $readable;

    /**
    * Creates a new PSR-7 stream.
    *
    * @param string|resource|StreamInterface $body
    *
    * @throws \InvalidArgumentException
    */
    public function __construct($stream = 'php://temp', string $mode = 'wb+')
    {
        if (\is_string($stream)) {
            $stream = '' === $stream ? false : @\fopen($stream, $mode);

            if (false === $stream) {
                throw new \RuntimeException('The stream or file cannot be opened.');
            }
        }

        if (!\is_resource($stream) || \get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Invalid stream provided. It must be a string stream identifier or stream resource.');
        }

        $this->resource = $stream;
    }

    /**
     * Closes the stream and any underlying resources when the instance is destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->resource) {
            $resource = $this->detach();
            \fclose($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = $this->size = null;
        $this->seekable = $this->writable = $this->readable = false;
        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = \fstat($this->resource);
        return $this->size = isset($stats['size']) ? (int) $stats['size'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available. Cannot tell position');
        }

        if (($result = \ftell($this->resource)) === false) {
            throw new \RuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return (!$this->resource || \feof($this->resource));
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }

        return $this->seekable = ($this->resource && $this->getMetadata('seekable'));
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available. Cannot seek position.');
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        if (\fseek($this->resource, $offset, $whence) !== 0) {
            throw new \RuntimeException('Error seeking within stream.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        if ($this->writable !== null) {
            return $this->writable;
        }

        if (!\is_string($mode = $this->getMetadata('mode'))) {
            return $this->writable = false;
        }

        return $this->writable = (
            \strpos($mode, 'w') !== false
            || \strpos($mode, '+') !== false
            || \strpos($mode, 'x') !== false
            || \strpos($mode, 'c') !== false
            || \strpos($mode, 'a') !== false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available. Cannot write.');
        }

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable.');
        }

        $this->size = null;

        if (($result = \fwrite($this->resource, $string)) === false) {
            throw new \RuntimeException('Error writing to stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        if ($this->readable !== null) {
            return $this->readable;
        }

        if (!\is_string($mode = $this->getMetadata('mode'))) {
            return $this->readable = false;
        }

        return $this->readable = (\strpos($mode, 'r') !== false || \strpos($mode, '+') !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available. Cannot read.');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        if (($result = \fread($this->resource, $length)) === false) {
            throw new \RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        if (($result = \stream_get_contents($this->resource)) === false) {
            throw new \RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (!$this->resource) {
            return $key ? null : [];
        }

        $metadata = \stream_get_meta_data($this->resource);

        if ($key === null) {
            return $metadata;
        }

        if (\array_key_exists($key, $metadata)) {
            return $metadata[$key];
        }

        return null;
    }
}
