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

namespace Biurad\Git\Commit;

use Biurad\Git\{GitObject, Repository};

/**
 * Represents a Blob commit.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Blob extends GitObject
{
    protected ?string $content = null, $mimetype = null;

    public function __construct(Repository $repository, string $hash = null, protected ?int $mode = null)
    {
        parent::__construct($repository, $hash);
    }

    public function getMode(): ?int
    {
        return $this->mode;
    }

    /**
     * Returns content of the blob.
     *
     * @throws \RuntimeException Error occurred while getting content of blob
     */
    public function getContent(): string
    {
        if (null === $this->content) {
            $this->content = $this->repository->run('cat-file', ['-p', $this->__toString()]);

            if (empty($this->content) || 0 !== $this->repository->getExitCode()) {
                throw new \RuntimeException(\sprintf('Blob "%s" content failed to read', $this->__toString()));
            }
        }

        return $this->content;
    }

    /**
     * Determine the mimetype of the blob.
     */
    public function getMimetype(): string
    {
        if (null === $this->mimetype) {
            $finfo = new \finfo(\FILEINFO_MIME);
            $this->mimetype = $finfo->buffer($this->getContent());
        }

        return $this->mimetype;
    }

    /**
     * Determines if file is binary.
     */
    public function isBinary(): bool
    {
        return 1 !== \preg_match('#^(?|text/|application/xml)#', $this->getMimetype());
    }

    /**
     * Determines if file is text.
     */
    public function isText(): bool
    {
        return !$this->isBinary();
    }
}
