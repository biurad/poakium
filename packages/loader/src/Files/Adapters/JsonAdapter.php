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

/**
 * Reading and generating JSON files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class JsonAdapter extends Adapter
{
    /**
     * Reads configuration from JSON data.
     *
     * @param  string $string
     *
     * @return array|bool
     */
    protected function processFrom(string $string)
    {
        return json_decode($string, true);
    }


    /**
     * Generates configuration in JSON format.
     *
     * @param array $data
     * @return false|string
     */
	protected function processDump(array $data)
	{
		return json_encode(
            $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
	}
}
