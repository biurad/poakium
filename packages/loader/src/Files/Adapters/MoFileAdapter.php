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
use BiuradPHP\Loader\Exceptions\FileLoadingException;

/**
 * Reading and generating Mo files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class MoFileAdapter extends AbstractAdapter
{
    protected $pos = 0;

    protected $str;

    protected $len;

    protected $endian;

    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return 'mo' === \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));
    }

    /**
     * Reads configuration from Mo data.
     *
     * @param string $string
     *
     * @return array
     */
    protected function processFrom(string $string): array
    {
        $this->endian = 'V';
        $this->str    = $string;
        $this->len    = \strlen($string);

        $magic = $this->readInt() & 0xffffffff;

        if ($magic === 0x950412de) {
            // Low endian.
            $this->endian = 'V';
        } elseif ($magic === 0xde120495) {
            // Big endian.
            $this->endian = 'N';
        } else {
            throw new FileLoadingException('Not a Gettext file (.mo).');
        }

        // Skip revision number.
        $rev = $this->readInt();
        // Total count.
        $total = $this->readInt();
        // Offset of original table.
        $originals = $this->readInt();
        // Offset of translation table.
        $translations = $this->readInt();

        // Each table consists of string length and offset of the string.
        $this->seek($originals);
        $table_originals = $this->readIntArray($total * 2);
        $this->seek($translations);
        $table_translations = $this->readIntArray($total * 2);

        $items = [];

        for ($i = 0; $i < $total; $i++) {
            $this->seek($table_originals[$i * 2 + 2]);

            // TODO: Original string can have context concatenated on it. We do not yet support that.
            $original = $this->read($table_originals[$i * 2 + 1]);

            if ($original) {
                $this->seek($table_translations[$i * 2 + 2]);

                // TODO: Plural forms are stored by letting the plural of the original string follow,
                // TODO: the singular of the original string, separated through a NUL byte.
                $translated       = $this->read($table_translations[$i * 2 + 1]);
                $items[$original] = $translated;
            }
        }

        return $items;
    }

    /**
     * Generates configuration in Mo format.
     *
     * @param array $data
     *
     * @return false|string
     */
    protected function processDump(array $data): string
    {
        throw new FileGeneratingException('Generating array to mo not supported for .mo files.');
    }

    /**
     * @return int
     */
    protected function readInt()
    {
        $read = $this->read(4);

        if ($read === false) {
            return false;
        }

        $read = \unpack($this->endian, $read);

        return \array_shift($read);
    }

    /**
     * @param $count
     *
     * @return array
     */
    protected function readIntArray($count)
    {
        return \unpack($this->endian . $count, $this->read(4 * $count));
    }

    /**
     * @param $bytes
     *
     * @return string
     */
    private function read($bytes)
    {
        $data = \substr($this->str, $this->pos, $bytes);
        $this->seek($this->pos + $bytes);

        return $data;
    }

    /**
     * @param $pos
     *
     * @return mixed
     */
    private function seek($pos)
    {
        $this->pos = $pos < $this->len ? $pos : $this->len;

        return $this->pos;
    }
}
