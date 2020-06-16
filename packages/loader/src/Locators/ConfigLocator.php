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

namespace BiuradPHP\Loader\Locators;

use BiuradPHP\Loader\Exceptions\FileLoadingException;
use BiuradPHP\Loader\Files\Adapters;
use BiuradPHP\Loader\Interfaces\DataInterface;
use BiuradPHP\Loader\Interfaces\FileAdapterInterface;
use InvalidArgumentException;
use RuntimeException;

class ConfigLocator
{
    protected const INCLUDES_KEY = 'includes';

    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var array
     */
    protected $loadedFiles = [];

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var FileAdapterInterface[] drivers
     */
    private $adapters = [
        Adapters\PhpFileAdapter::class,
        Adapters\IniFileAdapter::class,
        Adapters\XmlFileAdapter::class,
        Adapters\NeonFileAdapter::class,
        Adapters\JsonFileAdapter::class,
        Adapters\YamlFileAdapter::class,
        Adapters\MoFileAdapter::class,
        Adapters\CsvFileAdapter::class,
    ];

    /**
     * Reads configuration from file.
     *
     * @param string    $file
     * @param null|bool $merge
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function loadFile(string $file, ?bool $merge = true): array
    {
        if (isset($this->loadedFiles[$file])) {
            throw new RuntimeException("Recursive included file '$file'");
        }
        $this->loadedFiles[$file] = true;

        $this->dependencies[] = $file;
        $data                 = $this->getAdapter($file)->fromFile($file);

        $res = [];

        if (isset($data[self::INCLUDES_KEY])) {
            if (!$this->isList($data[self::INCLUDES_KEY])) {
                throw new InvalidArgumentException(
                    \sprintf('Expected \'included\' to contain a list of paths, invalid type supplied')
                );
            }

            foreach ($data[self::INCLUDES_KEY] as $include) {
                $include = $this->expandIncludedFile($include, $file);
                $res     = $this->mergeTree($this->loadFile($include, $merge), $res);
            }
        }
        unset($data[self::INCLUDES_KEY], $this->loadedFiles[$file]);

        if ($merge === false) {
            $res[] = $data;
        } else {
            $res = $this->mergeTree($data, $res);
        }

        return $res;
    }

    /**
     * Read configuration from multiple files and merge them.
     *
     * @param array $files
     * @param bool  $merge
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function loadFiles(array $files, $merge = true)
    {
        $config = [];

        foreach ($files as $file) {
            $config = $this->mergeTree($config, $this->loadFile($file, $merge));
        }

        return $config;
    }

    /**
     * Save's configuration to file.
     *
     * @param array|DataInterface $data
     * @param string              $file
     */
    public function saveFile(array $data, string $file): void
    {
        if (!\is_file($file) || !\is_readable($file)) {
            throw new FileLoadingException("File '$file' is missing or is not readable.");
        }

        $fileStatus = \file_put_contents($file, $this->getAdapter($file)->dump($data));

        // Invalidate configuration file from the opcache.
        if (
            \function_exists('opcache_invalidate') &&
            \filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN)
        ) {
            // PHP 5.5.5+
            @opcache_invalidate($file, true);
        }

        // Touch the directory as well, thus marking it modified.
        @\touch(\dirname($file));

        if (false === $fileStatus) {
            throw new InvalidArgumentException("Cannot write file '$file'.");
        }
    }

    /**
     * Returns configuration files.
     */
    public function getDependencies(): array
    {
        return \array_unique($this->dependencies);
    }

    /**
     * Expands included file name.
     *
     * @param string $includedFile
     * @param string $mainFile
     *
     * @return string
     */
    public function expandIncludedFile(string $includedFile, string $mainFile): string
    {
        return \preg_match('#([a-z]+:)?[/\\\\]#Ai', $includedFile) // is absolute
            ? $includedFile
            : \dirname($mainFile) . '/' . $includedFile;
    }

    /**
     * Registers adapter for given file extension.
     *
     * @param FileAdapterInterface $adapter
     *
     * @return static
     */
    public function addAdapter(FileAdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;

        return $this;
    }

    public function getAdapter(string $file): FileAdapterInterface
    {
        $adapters = \array_map(function ($adapter) {
            return \is_object($adapter) ? $adapter : new $adapter();
        }, $this->adapters);

        foreach ($adapters as $adapter) {
            if ($adapter->supports($file)) {
                return $adapter;
            }
        }

        throw new RuntimeException(
            \sprintf('Filename "%s" is missing an extension and cannot be auto-detected', $file)
        );
    }

    /**
     * Finds whether a variable is a zero-based integer indexed array.
     *
     * @param mixed $value
     */
    private function isList($value): bool
    {
        return \is_array($value) && (!$value || \array_keys($value) === \range(0, \count($value) - 1));
    }

    /**
     * Recursively appends elements of remaining keys from the second array to the first.
     */
    private function mergeTree(array $arr1, array $arr2): array
    {
        $res = $arr1 + $arr2;

        foreach (\array_intersect_key($arr1, $arr2) as $k => $v) {
            if (\is_array($v) && \is_array($arr2[$k])) {
                $res[$k] = $this->mergeTree($v, $arr2[$k]);
            }
        }

        return $res;
    }
}
