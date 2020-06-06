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

namespace BiuradPHP\Loader\Files;

use BiuradPHP\Loader\Exceptions\FileGeneratingException;

/**
 * ConfigCache caches arbitrary content in files on disk.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
class ConfigCache
{
    /**
     * @var string
     */
    private $file;

    /**
     * @param string $file he absolute cache path
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Gets the cache file path.
     *
     * @return string The cache file path
     */
    public function getPath(): string
    {
        return $this->file;
    }

    /**
     * Returns a cached file and (re-)initializes it if necessary.
     *
     * @param string $content The content to write into the cache
     *
     * @return ConfigCacheInterface The cache instance
     * @throws FileGeneratingException When the cache file cannot be written
     */
    public function write(string $content)
    {
        $mode = 0666;
        $umask = umask();

        if (!is_file($this->file)) {
            if (false === @file_put_contents($this->file, $content)) {
                throw new FileGeneratingException(sprintf('Failed to write file "%s".', $this->file));
            }

            @chmod($this->file, $mode & ~$umask);

            if (\function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
                @opcache_invalidate($this->file, true);
            }
        }

        return;
    }
}
