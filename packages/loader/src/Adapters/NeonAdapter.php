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

namespace BiuradPHP\Loader\Adapters;

use Nette\Neon;

/**
 * Reading and generating NEON files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class NeonAdapter extends Adapter
{
    /**
     * Reads configuration from NEON data.
     *
     * @param string $string
     *
     * @return array|bool
     */
    protected function processFrom(string $string)
    {
        return Neon\Neon::decode($string);
    }

    /**
     * Generates configuration in NEON format.
     *
     * @param array $data
     * @return string
     */
    protected function processDump(array $data)
    {
        $class = __CLASS__;

        return "# generated by $class\n\n".Neon\Neon::encode(
            $data, Neon\Neon::BLOCK
        );
    }
}
