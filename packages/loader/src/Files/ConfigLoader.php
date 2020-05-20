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

use InvalidArgumentException;
use BiuradPHP\Loader\Interfaces\AdapterInterface;
use BiuradPHP\Loader\Interfaces\DataInterface;
use RuntimeException;

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
     *
     * @param string $file
     * @param bool|null $merge
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function loadFile(string $file, ?bool $merge = true): array
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new InvalidArgumentException("File '$file' is missing or is not readable.");
        }

        if (isset($this->loadedFiles[$file])) {
            throw new RuntimeException("Recursive included file '$file'");
        }
        $this->loadedFiles[$file] = true;

        $this->dependencies[] = $file;
        $data = $this->getAdapter($file)->fromFile($file);

        $res = [];
        if (isset($data[self::INCLUDES_KEY])) {
            if (!$this->isList($data[self::INCLUDES_KEY])) {
                throw new InvalidArgumentException(sprintf('Expected \'included\' to contain a list of paths, invalid type supplied'));
            }
            foreach ($data[self::INCLUDES_KEY] as $include) {
                $include = $this->expandIncludedFile($include, $file);
                $res = $this->mergeTree($this->loadFile($include, $merge), $res);
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
     * @param bool $merge
     *
     * @return array
     * @throws InvalidArgumentException
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
     * @param string       $file
     */
    public function save(array $data, string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new InvalidArgumentException("File '$file' is missing or is not readable.");
        }

        if ((is_object($data) && !($data instanceof DataInterface)) || (!is_object($data) && !is_array($data))
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s $config should be an array or instance of %s\Config\Config',
                __METHOD__,
                __NAMESPACE__
            ));
        }

        if ((is_object($data) && ($data instanceof DataInterface))) {
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
     * @param string $includedFile
     * @param string $mainFile
     *
     * @return string
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
     * @param string $extension
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
            throw new RuntimeException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected',
                $file
            ));
        }

        return is_object($this->adapters[$extension]) ? $this->adapters[$extension] : new $this->adapters[$extension]();
    }

    /**
	 * Finds whether a variable is a zero-based integer indexed array.
	 * @param  mixed  $value
	 */
	private function isList($value): bool
	{
		return is_array($value) && (!$value || array_keys($value) === range(0, count($value) - 1));
	}

    /**
	 * Recursively appends elements of remaining keys from the second array to the first.
	 */
	private function mergeTree(array $arr1, array $arr2): array
	{
		$res = $arr1 + $arr2;
		foreach (array_intersect_key($arr1, $arr2) as $k => $v) {
			if (is_array($v) && is_array($arr2[$k])) {
				$res[$k] = self::mergeTree($v, $arr2[$k]);
			}
		}
		return $res;
	}
}
