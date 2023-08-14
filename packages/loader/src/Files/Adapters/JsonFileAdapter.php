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
 * Reading and generating JSON files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class JsonFileAdapter extends AbstractAdapter
{
    public function supports(string $file): bool
    {
        return 'json' === \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));
    }

    /**
     * Reads configuration from JSON data.
     */
    protected function processFrom(string $string): array
    {
        return \json_decode($string, true);
    }

    /**
     * Generates configuration in JSON format.
     *
     * @return false|string
     */
    protected function processDump(array $data): string
    {
        $json = \json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new FileGeneratingException('Unable to generate json from provided data');
        }

        return $json;
    }
}
