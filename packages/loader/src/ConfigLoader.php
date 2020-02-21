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
 * @link      https://www.biurad.com/projects/loadermanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader;

use BiuradPHP\Config\Config;
use InvalidArgumentException;
use Nette;
use Nette\Utils\Validators;
use BiuradPHP\Loader\Interfaces\AdapterInterface;

class ConfigLoader
{
    protected const INCLUDES_KEY = 'includes';

    /**
     * @var array drivers
     */
    private $adapters = [
        'php' => Adapters\PhpAdapter::class,
        'ini' => Adapters\IniAdapter::class,
        'xml' => Adapters\XmlAdapter::class,
        'neon' => Adapters\NeonAdapter::class,
        'json' => Adapters\JsonAdapter::class,
        'yml' => Adapters\YamlAdapter::class,
        'yaml' => Adapters\YamlAdapter::class,
    ];

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
     * Reads configuration from file.
     */
    public function loadFile(string $file, ?bool $merge = true): array
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new Nette\FileNotFoundException("File '$file' is missing or is not readable.");
        }

        if (isset($this->loadedFiles[$file])) {
            throw new Nette\InvalidStateException("Recursive included file '$file'");
        }
        $this->loadedFiles[$file] = true;

        $this->dependencies[] = $file;
        $data = $this->getAdapter($file)->fromFile($file);

        $res = [];
        if (isset($data[self::INCLUDES_KEY])) {
            Validators::assert($data[self::INCLUDES_KEY], 'list', "section 'includes' in file '$file'");
            foreach ($data[self::INCLUDES_KEY] as $include) {
                $include = $this->expandIncludedFile($include, $file);
                $res = Nette\Utils\Arrays::mergeTree($this->loadFile($include, $merge), $res);
            }
        }
        unset($data[self::INCLUDES_KEY], $this->loadedFiles[$file]);

        if ($merge === false) {
            $res[] = $data;
        } else {
            $res = Nette\Utils\Arrays::mergeTree($data, $res);
        }

        return $res;
    }

    /**
     * Read configuration from multiple files and merge them.
     *
     * @param array $files
     * @param bool  $merge
     *
     * @return array
     */
    public function loadFiles(array $files, $merge = true)
    {
        $config = [];

        foreach ($files as $file) {
            $config = Nette\Utils\Arrays::mergeTree($config, $this->loadFile($file, $merge));
        }

        return $config;
    }

    /**
     * Save's configuration to file.
     *
     * @param array|Config $data
     * @param string       $file
     */
    public function save(array $data, string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new Nette\FileNotFoundException("File '$file' is missing or is not readable.");
        }

        if ((is_object($data) && !($data instanceof Config)) || (!is_object($data) && !is_array($data))
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s $config should be an array or instance of %s\Config\Config',
                __METHOD__,
                __NAMESPACE__
            ));
        }

        if ((is_object($data) && ($data instanceof Config))) {
            $data = $data->toArray();
        }

        if (!file_put_contents($file, $this->getAdapter($file)->dump($data))) {
            throw new InvalidArgumentException("Cannot write file '$file'.");
        }
    }

    /**
     * Returns configuration files.
     */
    public function getDependencies(): array
    {
        return array_unique($this->dependencies);
    }

    /**
     * Expands included file name.
     */
    public function expandIncludedFile(string $includedFile, string $mainFile): string
    {
        return preg_match('#([a-z]+:)?[/\\\\]#Ai', $includedFile) // is absolute
            ? $includedFile
            : dirname($mainFile).'/'.$includedFile;
    }

    /**
     * Registers adapter for given file extension.
     *
     * @param string|Interfaces\AdapterInterface $adapter
     *
     * @return static
     */
    public function addAdapter(string $extension, $adapter)
    {
        $this->adapters[mb_strtolower($extension)] = $adapter;

        return $this;
    }

    public function getAdapter(string $file): AdapterInterface
    {
        $extension = mb_strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!isset($this->adapters[$extension])) {
            throw new InvalidArgumentException("Unknown file extension '$file'.");
        }

        if (!isset($extension)) {
            throw new Nette\InvalidStateException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected',
                $file
            ));
        }

        return is_object($this->adapters[$extension]) ? $this->adapters[$extension] : new $this->adapters[$extension]();
    }
}
