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
