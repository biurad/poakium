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

use GuzzleHttp\Psr7\UploadedFile as Psr7UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    /** @var UploadedFileInterface */
    private $uploadedFile;

    /**
     * @param resource|StreamInterface|string $streamOrFile
     */
    public function __construct($streamOrFile, ?int $size, int $errorStatus, string $clientFilename = null, string $clientMediaType = null)
    {
        $this->uploadedFile = new Psr7UploadedFile($streamOrFile, $size, $errorStatus, $clientFilename, $clientMediaType);
    }

    /**
     * Exchanges the underlying uploadedFile with another.
     */
    public function withUploadFile(UploadedFileInterface $uploadedFile): UploadedFileInterface
    {
        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        return $this->uploadedFile->getStream();
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath): void
    {
        $this->uploadedFile->moveTo($targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->uploadedFile->getSize();
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
        return $this->uploadedFile->getClientFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->uploadedFile->getClientMediaType();
    }
}
