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

namespace BiuradPHP\Loader\Files\Adapters;

use BiuradPHP\Loader\Exceptions\FileGeneratingException;

/**
 * Reading and generating JSON files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class JsonFileAdapter extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return 'json' === \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));
    }

    /**
     * Reads configuration from JSON data.
     *
     * @param string $string
     *
     * @return array
     */
    protected function processFrom(string $string): array
    {
        return \json_decode($string, true);
    }

    /**
     * Generates configuration in JSON format.
     *
     * @param array $data
     *
     * @return false|string
     */
    protected function processDump(array $data): string
    {
        $json = \json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new FileGeneratingException('Unable to generate json from provided data');
        }

        return $json;
    }
}
