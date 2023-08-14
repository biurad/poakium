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

namespace Biurad\Loader\Files;

use Biurad\Loader\Locators\UniformResourceLocator;

/**
 * Implements FilesystemIterator for uniform resource locator.
 *
 * This was first implemented by Grav CMS.
 * thanks to RocketTheme Team. This code has been modified for performance.
 *
 * @author RocketTheme
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class UniformResourceIterator extends \FilesystemIterator
{
    protected \FilesystemIterator $iterator;

    /** @var array<string,bool> */
    protected array $found;

    /** @var array<int,string> */
    protected array $stack = [];

    /**
     * UniformResourceIterator constructor.
     */
    public function __construct(protected string $path, protected int $flags = UniformResourceLocator::FLAG, protected ?UniformResourceLocator $locator = null)
    {
        if (null === $locator) {
            throw new \InvalidArgumentException(\sprintf('Use the %s::getIterator() method instead', UniformResourceLocator::class));
        }

        $this->rewind();
    }

    public function __toString(): string
    {
        return $this->iterator->__toString();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if ($this->flags & static::CURRENT_AS_SELF) {
            return $this;
        }

        return $this->iterator->current();
    }

    public function key(): string
    {
        return $this->iterator->key();
    }

    public function next(): void
    {
        do {
            $found = $this->findNext();
        } while (null !== $found && !empty($this->found[$found]));

        if (null !== $found) {
            // Mark the file as found.
            $this->found[$found] = true;
        }
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->found = [];
        $this->stack = $this->locator->findResources($this->path);

        if (!$this->nextIterator()) {
            throw new \RuntimeException('Failed to open dir: '.$this->path.' does not exist.');
        }

        // Find the first valid entry.
        while (!$this->valid()) {
            if (!$this->nextIterator()) {
                return;
            }
        }

        // Mark the first file as found.
        $this->found[$this->getFilename()] = true;
    }

    public function getUrl(): string
    {
        $path = $this->path.('/' === $this->path[\strlen($this->path) - 1] ? '' : '/');

        return $path.$this->iterator->getFilename();
    }

    public function seek($position): void
    {
        throw new \RuntimeException('Seek not implemented');
    }

    #[\ReturnTypeWillChange]
    public function getATime(): bool|int
    {
        return $this->iterator->getATime();
    }

    public function getBasename(?string $suffix = ''): string
    {
        return $this->iterator->getBasename($suffix);
    }

    #[\ReturnTypeWillChange]
    public function getCTime(): bool|int
    {
        return $this->iterator->getCTime();
    }

    public function getExtension(): string
    {
        return $this->iterator->getExtension();
    }

    public function getFilename(): string
    {
        return $this->iterator->getFilename();
    }

    #[\ReturnTypeWillChange]
    public function getGroup(): bool|int
    {
        return $this->iterator->getGroup();
    }

    #[\ReturnTypeWillChange]
    public function getInode(): bool|int
    {
        return $this->iterator->getInode();
    }

    #[\ReturnTypeWillChange]
    public function getMTime(): bool|int
    {
        return $this->iterator->getMTime();
    }

    #[\ReturnTypeWillChange]
    public function getOwner(): bool|int
    {
        return $this->iterator->getOwner();
    }

    public function getPath(): string
    {
        return $this->iterator->getPath();
    }

    public function getPathname(): string
    {
        return $this->iterator->getPathname();
    }

    #[\ReturnTypeWillChange]
    public function getPerms(): bool|int
    {
        return $this->iterator->getPerms();
    }

    #[\ReturnTypeWillChange]
    public function getSize(): bool|int
    {
        return $this->iterator->getSize();
    }

    #[\ReturnTypeWillChange]
    public function getType(): bool|string
    {
        return $this->iterator->getType();
    }

    public function isDir(): bool
    {
        return $this->iterator->isDir();
    }

    public function isDot(): bool
    {
        return $this->iterator->isDot();
    }

    public function isExecutable(): bool
    {
        return $this->iterator->isExecutable();
    }

    public function isFile(): bool
    {
        return $this->iterator->isFile();
    }

    public function isLink(): bool
    {
        return $this->iterator->isLink();
    }

    public function isReadable(): bool
    {
        return $this->iterator->isReadable();
    }

    public function isWritable(): bool
    {
        return $this->iterator->isWritable();
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function setFlags(int $flags = UniformResourceLocator::FLAG): void
    {
        $this->flags = $flags;
        $this->iterator?->setFlags($this->flags);
    }

    private function findNext(): ?string
    {
        $this->iterator->next();

        while (!$this->valid()) {
            if (!$this->nextIterator()) {
                return null;
            }
        }

        return $this->getFilename();
    }

    private function nextIterator(): bool
    {
        // Move to the next iterator if it exists.
        $hasNext = null !== $path = \array_shift($this->stack);

        if ($hasNext) {
            $this->iterator = new \FilesystemIterator($path, $this->getFlags());
        }

        return $hasNext;
    }
}
