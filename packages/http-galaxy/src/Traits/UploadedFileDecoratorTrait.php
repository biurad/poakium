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

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

trait UploadedFileDecoratorTrait
{
    /** @var UploadedFileInterface */
    protected $uploadedFile;

    public function getStream(): StreamInterface
    {
        return $this->uploadedFile->getStream();
    }

    public function moveTo($targetPath): void
    {
        $this->uploadedFile->moveTo($targetPath);
    }

    public function getSize(): ?int
    {
        return $this->uploadedFile->getSize();
    }

    public function getError(): int
    {
        return $this->uploadedFile->getError();
    }

    public function getClientFilename(): ?string
    {
        return $this->uploadedFile->getClientFilename();
    }

    public function getClientMediaType(): ?string
    {
        return $this->uploadedFile->getClientMediaType();
    }
}
