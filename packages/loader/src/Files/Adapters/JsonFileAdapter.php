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
        return 'json' === strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    /**
     * Reads configuration from JSON data.
     *
     * @param  string $string
     *
     * @return array
     */
    protected function processFrom(string $string): array
    {
        return json_decode($string, true);
    }


    /**
     * Generates configuration in JSON format.
     *
     * @param array $data
     * @return false|string
     */
	protected function processDump(array $data): string
	{
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileGeneratingException('Unable to generate json from provided data');
        }

        return $json;
	}
}
