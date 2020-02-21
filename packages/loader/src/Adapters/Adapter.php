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

namespace BiuradPHP\Loader\Adapters;

use BiuradPHP\Loader\Interfaces\AdapterInterface;
use InvalidArgumentException;
use RuntimeException;
use Traversable;


/**
 * Reading and generating PHP files.
 *
 * @author Zend Technologies USA Inc <http://www.zend.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
abstract class Adapter implements AdapterInterface
{
    /**
     * Directory of the file to process.
     *
     * @var string
     */
    protected $directory;

	/**
     * Reads configuration from config file.
     *
     * @param  string $filename
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public function fromFile(string $filename)
    {
        if (! is_file($filename) || ! is_readable($filename)) {
            throw new RuntimeException(
                sprintf("File '%s' doesn't exist or not readable", $filename
            ));
        }

        $this->directory = dirname($filename);

        set_error_handler(
            function ($error, $message = '') use ($filename) {
                throw new RuntimeException(
                    sprintf('Error reading config file "%s": %s', $filename, $message), $error
                );
            },
            E_WARNING
        );
        $config = file_get_contents($filename);
        restore_error_handler();

        return (array) $this->fromString($config);
    }

    /**
     * Reads configuration from config data.
     *
     * @param  string $string
     *
     * @return array|bool
     *
     * @throws RuntimeException
     */
    public function fromString($string)
    {
        if (empty($string)) {
            return [];
        }
        $this->directory = null;

        set_error_handler(
            function ($error, $message = '') {
                throw new RuntimeException(
                    sprintf('Error reading config string: %s', $message), $error
                );
            },
            E_WARNING
        );
        $config = (string) $string;
        restore_error_handler();

        return $this->processFrom($config);
    }


	/**
	 * Generates configuration in designed format.
     *
     * @param mixed $data
     *
     * @throws InvalidArgumentException
	 */
	public function dump($data): string
	{
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        } elseif (! is_array($data)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects an array or Traversable config');
        }

		return $this->processDump($data);
    }

    /**
     * @param string $config
     *
     * @return array
     */
    abstract protected function processFrom(string $config);

    /**
     * @param array $config
     *
     * @return string|array
     */
    abstract protected function processDump(array $config);
}
