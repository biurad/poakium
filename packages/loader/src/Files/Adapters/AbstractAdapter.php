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

namespace BiuradPHP\Loader\Files\Adapters;

use BiuradPHP\Loader\Exceptions\FileGeneratingException;
use BiuradPHP\Loader\Exceptions\FileLoadingException;
use BiuradPHP\Loader\Interfaces\DataInterface;
use BiuradPHP\Loader\Interfaces\FileAdapterInterface;


/**
 * Reading and generating PHP files.
 *
 * @author Zend Technologies USA Inc <http://www.zend.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
abstract class AbstractAdapter implements FileAdapterInterface
{
	/**
     * {@inheritdoc}
     */
    public function fromFile(string $filename): array
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new FileLoadingException(sprintf("File '%s' doesn't exist or not readable", $filename));
        }

        set_error_handler(
            function ($error, $message = '') use ($filename) {
                throw new FileLoadingException(sprintf('Error reading config file "%s": %s', $filename, $message), $error);
            },
            E_WARNING
        );
        $config = file_get_contents($filename);
        restore_error_handler();

        return (array) $this->fromString($config);
    }

    /**
     * {@inheritdoc}
     */
    public function fromString(string $string): array
    {
        if (empty($string)) {
            return [];
        }

        set_error_handler(
            function ($error, $message = '') {
                throw new FileLoadingException(sprintf('Error reading config string: %s', $message), $error);
            },
            E_WARNING
        );
        $config = (string) $string;
        restore_error_handler();

        return $this->processFrom($config);
    }


    /**
     * {@inheritdoc}
     */
	public function dump($data): string
	{
        if (!is_array($data = $this->resolveData($data))) {
            throw new FileGeneratingException(__METHOD__ . ' expects an array, Traversable, stdClass, or JsonSerializable config');
        }

		return $this->processDump($data);
    }

    /**
     * {@inheritdoc}
     */
    abstract public function supports(string $file): bool;

    /**
     * @param string $config
     *
     * @return array
     */
    abstract protected function processFrom(string $config): array;

    /**
     * @param array|object|\Traversable|\JsonSerializable $config
     *
     * @return string
     */
    abstract protected function processDump(array $config): string;

    /**
     * @param array|object|\Traversable|\JsonSerializable $data
     * @return array
     */
    private function resolveData($data): array
    {
        if ($data instanceof \stdClass) {
            return (array) $data;
        }

        if ($data instanceof \Traversable) {
            return iterator_to_array($data);
        }

        if (class_exists(\JsonSerializable::class) && $data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        if ($data instanceof DataInterface) {
            return $data->toArray();
        }

        return $data;
    }
}
