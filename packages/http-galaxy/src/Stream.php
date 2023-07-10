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
    /** @var resource|null */
    private $resource;

    private ?bool $size;
    private ?bool $seekable;
    private ?bool $writable;
    private ?bool $readable;

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
            if (\file_exists($stream)) {
                $stream = @\fopen($stream, $mode);
            } else {
                $resource = \fopen('php://temp', 'rw+');
                \fwrite($resource, $stream);
                $stream = $resource;
            }
        }

        if (!\is_resource($stream) || 'stream' !== \get_resource_type($stream)) {
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
        if (null === $this->resource) {
            return null;
        }

        if (null !== $this->size) {
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
        return !$this->resource || \feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        if (null !== $this->seekable) {
            return $this->seekable;
        }

        return $this->seekable = ($this->resource && $this->getMetadata('seekable'));
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available. Cannot seek position.');
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        if (0 !== \fseek($this->resource, $offset, $whence)) {
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
        if (null !== $this->writable) {
            return $this->writable;
        }

        if (!\is_string($mode = $this->getMetadata('mode'))) {
            return $this->writable = false;
        }

        return $this->writable = \str_contains($mode, 'w')
            || \str_contains($mode, '+')
            || \str_contains($mode, 'x')
            || \str_contains($mode, 'c')
            || \str_contains($mode, 'a')
        ;
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
        if (null !== $this->readable) {
            return $this->readable;
        }

        if (!\is_string($mode = $this->getMetadata('mode'))) {
            return $this->readable = false;
        }

        return $this->readable = (\str_contains($mode, 'r') || \str_contains($mode, '+'));
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

        if (null === $key) {
            return $metadata;
        }

        if (\array_key_exists($key, $metadata)) {
            return $metadata[$key];
        }

        return null;
    }
}
