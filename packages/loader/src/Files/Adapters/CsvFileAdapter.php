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

/**
 * Reading and generating Csv files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class CsvFileAdapter extends AbstractAdapter
{
    public function supports(string $file): bool
    {
        return \in_array(\strtolower(\pathinfo($file, \PATHINFO_EXTENSION)), ['csv', 'tsv'], true);
    }

    /**
     * Reads configuration from Csv data.
     */
    protected function processFrom(string $string): array
    {
        $lines = \preg_split('/\r\n|\r|\n/', $string);

        if (false === $lines) {
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
        } catch (\Exception $e) {
            throw new FileGeneratingException('Badly formatted CSV line: '.$line, 0, $e);
        }

        return $list;
    }

    /**
     * Generates configuration in Csv format.
     */
    protected function processDump(array $data): string
    {
        if (0 === \count($data)) {
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

    protected function encodeLine(array $line, string $delimiter): string
    {
        foreach ($line as &$value) {
            $value = $this->escape((string) $value);
        }
        unset($value);

        return \implode($delimiter, $line)."\n";
    }

    protected function escape(string $value): string
    {
        if (\preg_match('/[,"\r\n]/u', $value)) {
            $value = '"'.\preg_replace('/"/', '""', $value).'"';
        }

        return $value;
    }
}
