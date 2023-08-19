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

namespace Biurad\Loader\Files\Adapters;

use Biurad\Loader\Exceptions\FileGeneratingException;
use Biurad\Loader\Exceptions\FileLoadingException;
use Biurad\Loader\Exceptions\LoaderException;

/**
 * Reading and generating PHP files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class AbstractAdapter
{
    /**
     * Read from a file and create an array.
     */
    public function fromFile(string $filename): array
    {
        if (!\is_file($filename) || !\is_readable($filename)) {
            throw new FileLoadingException(\sprintf("File '%s' doesn't exist or not readable", $filename));
        }

        \set_error_handler(
            function ($error, $message = '') use ($filename): void {
                throw new FileLoadingException(\sprintf('Error reading config file "%s": %s', $filename, $message), $error);
            },
            \E_WARNING
        );
        $config = \file_get_contents($filename);
        \restore_error_handler();

        return $this->fromString($config);
    }

    /**
     * Read from a string and create an array.
     */
    public function fromString(string $string): array
    {
        if (empty($string)) {
            return [];
        }

        \set_error_handler(
            function ($error, $message = ''): void {
                throw new FileLoadingException(\sprintf('Error reading config string: %s', $message), $error);
            },
            \E_WARNING
        );
        $config = (string) $string;
        \restore_error_handler();

        return $this->processFrom($config);
    }

    /**
     * Generates configuration string.
     *
     * Write a config object to a string.
     *
     * @param object|array $data e.g (JsonSerializable orTraversable)
     */
    public function dump(object|array $data): string
    {
        if (!\is_array($data = $this->resolveData($data))) {
            throw new FileGeneratingException(__METHOD__.' expects an array, Traversable, stdClass, or JsonSerializable config');
        }

        return $this->processDump($data);
    }

    /**
     * Check file supported extensions.
     */
    abstract public function supports(string $file): bool;

    abstract protected function processFrom(string $string): array;

    abstract protected function processDump(array $config): string;

    private function resolveData(object|array $data): array
    {
        if ($data instanceof \stdClass) {
            return (array) $data;
        }

        if ($data instanceof \Traversable) {
            return \iterator_to_array($data);
        }

        if (\class_exists(\JsonSerializable::class) && $data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        return is_array($data) ? $data : throw new LoaderException('Unsupported data type');
    }
}
