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
use Exception;

/**
 * Reading and generating Csv files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class CsvFileAdapter extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return \in_array(\strtolower(\pathinfo($file, \PATHINFO_EXTENSION)), ['csv', 'tsv'], true);
    }

    /**
     * Reads configuration from Csv data.
     *
     * @param string $string
     *
     * @return array
     */
    protected function processFrom(string $string): array
    {
        $lines = \preg_split('/\r\n|\r|\n/', $string);

        if ($lines === false) {
            throw new FileGeneratingException('Decoding CSV failed');
        }

        // Get the field names
        $header = \str_getcsv(\array_shift($lines), ',');

        // Get the data
        $list = [];
        $line = null;

        try {
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $csv_line = \str_getcsv($line, ',');

                    $list[] = \array_combine($header, $csv_line);
                }
            }
        } catch (Exception $e) {
            throw new FileGeneratingException('Badly formatted CSV line: ' . $line, 0, $e);
        }

        return $list;
    }

    /**
     * Generates configuration in Csv format.
     *
     * @param array $data
     *
     * @return false|string
     */
    protected function processDump(array $data): string
    {
        if (\count($data) === 0) {
            return '';
        }
        $header = \array_keys(\reset($data));

        // Encode the field names
        $string = $this->encodeLine($header, ',');

        // Encode the data
        foreach ($data as $row) {
            $string .= $this->encodeLine($row, ',');
        }

        return $string;
    }

    protected function encodeLine(array $line, $delimiter = null): string
    {
        foreach ($line as $key => &$value) {
            $value = $this->escape((string) $value);
        }
        unset($value);

        return \implode($delimiter, $line) . "\n";
    }

    protected function escape(string $value)
    {
        if (\preg_match('/[,"\r\n]/u', $value)) {
            $value = '"' . \preg_replace('/"/', '""', $value) . '"';
        }

        return $value;
    }
}
