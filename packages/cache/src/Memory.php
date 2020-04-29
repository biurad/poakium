<?php /** @noinspection PhpIncludeInspection */

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  CacheManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/cachemanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Cache;

use BiuradPHP\Cache\Interfaces\MemoryInterface;
use Throwable;

/**
 * File based memory storage.
 */
final class Memory implements MemoryInterface
{
    // data file extension
    private const EXTENSION = 'php';

    /** @var string */
    private $directory;

    /**
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $filename Cache filename.
     */
    public function loadData(string $section, string &$filename = null)
    {
        $filename = $this->getFilename($section);

        if (!file_exists($filename)) {
            return null;
        }

        try {
            return include($filename);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveData(string $section, $data): void
    {
        file_put_contents(
            $this->getFilename($section),
            '<?php return ' . var_export($data, true) . ';',
            0666,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Get extension to use for runtime data or configuration cache.
     *
     * @param string $name Runtime data file name (without extension).
     *
     * @return string
     */
    private function getFilename(string $name): string
    {
        //Runtime cache
        return sprintf(
            '%s/%s.%s',
            $this->directory,
            strtolower(str_replace(['/', '\\'], '-', $name)),
            self::EXTENSION
        );
    }
}
