<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/biurad-loader
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Resources;

use FilesystemIterator;
use RecursiveIterator;
use SeekableIterator;

/**
 * Implements recursive iterator over filesystem.
 *
 * @author RocketTheme
 * @license MIT
 * @license BSD-3-Clause
 */
class RecursiveUniformResourceIterator extends UniformResourceIterator implements SeekableIterator, RecursiveIterator
{
    protected $subPath;

    public function getChildren()
    {
        $subPath = $this->getSubPathName();

        return (new static($this->getUrl(), $this->flags, $this->locator))->setSubPath($subPath);
    }

    public function hasChildren($allow_links = null)
    {
        $allow_links = (bool) ($allow_links !== null ? $allow_links : $this->flags & FilesystemIterator::FOLLOW_SYMLINKS);

        return $this->iterator && $this->isDir() && !$this->isDot() && ($allow_links || !$this->isLink());
    }

    public function getSubPath()
    {
        return $this->subPath;
    }

    public function getSubPathName()
    {
        return ($this->subPath ? $this->subPath.'/' : '').$this->getFilename();
    }

    /**
     * @param $path
     *
     * @return $this
     *
     * @internal
     */
    public function setSubPath($path)
    {
        $this->subPath = $path;

        return $this;
    }
}
