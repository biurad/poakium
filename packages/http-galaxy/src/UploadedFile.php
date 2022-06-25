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

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as FileUploadedFile;

class UploadedFile implements UploadedFileInterface
{
    /** @var FileUploadedFile */
    private $uploadedFile;

    /** @var StreamInterface */
    private $stream;

    /** @var int|null */
    private $size;


    /** @var bool */
    private $isMoved = false;

    /**
     * @param resource|StreamInterface|string $streamOrFile
     */
    public function __construct($streamOrFile, ?int $size, int $errorStatus = null, string $clientFilename = null, string $clientMediaType = null)
    {
        if (is_resource($streamOrFile)) {
            $streamOrFile = \stream_get_meta_data($streamOrFile)['uri'];
        } elseif ($streamOrFile instanceof StreamInterface) {
            $streamOrFile = $streamOrFile->getMetadata('uri');
        }

        $this->size = $size;
        $this->uploadedFile = new FileUploadedFile($streamOrFile, $clientFilename, $clientMediaType, $errorStatus);
    }

    /**
     * Exchanges the underlying uploadedFile with another.
     */
    public function withUploadedFile(FileUploadedFile $uploadedFile): UploadedFileInterface
    {
        $new = clone $this;
        $new->uploadedFile = $uploadedFile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFile(): FileUploadedFile
    {
        return $this->uploadedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->isMoved) {
            throw new \RuntimeException('The stream is not available because it has been moved.');
        }

        if (null !== $this->stream) {
            return $this->stream;
        }

        try {
            return $this->stream = new Stream($this->uploadedFile->getPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException(\sprintf('The file "%s" cannot be opened.', $this->uploadedFile->getPath()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath): void
    {
        if ($this->isMoved) {
            throw new \RuntimeException('The file cannot be moved because it has already been moved.');
        }

        $this->uploadedFile->move($targetPath);
        $this->isMoved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size ?? $this->uploadedFile->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->uploadedFile->getError();
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->uploadedFile->getClientOriginalName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->uploadedFile->getClientMimeType();
    }
}
