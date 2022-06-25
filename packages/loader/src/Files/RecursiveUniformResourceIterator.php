<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Loader\Files;

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
        $allow_links = (bool) (
            $allow_links !== null ? $allow_links : $this->flags & FilesystemIterator::FOLLOW_SYMLINKS
        );

        return $this->iterator && $this->isDir() && !$this->isDot() && ($allow_links || !$this->isLink());
    }

    public function getSubPath()
    {
        return $this->subPath;
    }

    public function getSubPathName()
    {
        return ($this->subPath ? $this->subPath . '/' : '') . $this->getFilename();
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
