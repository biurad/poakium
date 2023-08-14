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

/**
 * Implements recursive iterator over filesystem.
 *
 * @author RocketTheme
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RecursiveUniformResourceIterator extends UniformResourceIterator implements \SeekableIterator, \RecursiveIterator
{
    protected string $subPath;

    public function getChildren(): self
    {
        $subPath = $this->getSubPathName();

        return (new static($this->getUrl(), $this->flags, $this->locator))->setSubPath($subPath);
    }

    public function hasChildren(int $allow_links = null): bool
    {
        $allow_links = (bool) (null !== $allow_links ? $allow_links : $this->flags & \FilesystemIterator::FOLLOW_SYMLINKS);

        return $this->iterator && $this->isDir() && !$this->isDot() && ($allow_links || !$this->isLink());
    }

    public function getSubPath(): string
    {
        return $this->subPath;
    }

    public function getSubPathName(): string
    {
        return ($this->subPath ? $this->subPath.'/' : '').$this->getFilename();
    }

    public function setSubPath(string $path): self
    {
        $this->subPath = $path;

        return $this;
    }
}
