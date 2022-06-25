<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command line.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\ConsoleLite;

use BiuradPHP\Toolbox\ConsoleLite\Exceptions\ExpectedException;

/**
 * Class Formatter.
 *
 * Output text in multiple columns, tables and more.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class Formatter extends Terminal
{
    /** @var Colors for coloring output */
    protected $colors;

    private $value;
    private $options = [
        'rowspan' => 1,
        'colspan' => 1,
    ];

    /**
     * Formatter constructor.
     *
     * @param Colors|null $colors
     */
    public function __construct(Colors $colors = null)
    {
        if ($colors) {
            $this->colors = $colors;
        } else {
            $this->colors = new Colors();
        }
        parent::__construct();
    }

    /**
     * The currently set border (defaults to ' ').
     *
     * @return string
     */
    public function getBorder()
    {
        return $this->border;
    }

    /**
     * Set the border. The border is set between each column. Its width is
     * added to the column widths.
     *
     * @param string $border
     */
    public function setBorder($border)
    {
        $this->border = $border;
    }

    /**
     * Width of the terminal in characters.
     *
     * initially autodetected
     *
     * @return int
     */
    public function getMaxWidth()
    {
        return (int) $this->max;
    }

    /**
     * Set the width of the terminal to assume (in characters).
     *
     * @param int $max
     */
    public function setMaxWidth($max)
    {
        $this->max = (int) $max;
    }

    /**
     * Takes an array with dynamic column width and calculates the correct width.
     *
     * Column width can be given as fixed char widths, percentages and a single * width can be given
     * for taking the remaining available space. When mixing percentages and fixed widths, percentages
     * refer to the remaining space after allocating the fixed width
     *
     * @param array $columns
     *
     * @throws ExpectedException
     *
     * @return int[]
     */
    protected function calculateColLengths($columns)
    {
        $idx = 0;
        $border = $this->strlen($this->border);
        $fixed = (count($columns) - 1) * $border; // borders are used already
        $fluid = -1;

        // first pass for format check and fixed columns
        foreach ($columns as $idx => $col) {
            // handle fixed columns
            if ((string) intval($col) === (string) $col) {
                $fixed += $col;
                continue;
            }
            // check if other colums are using proper units
            if (substr($col, -1) == '%') {
                continue;
            }
            if ($col == '*') {
                // only one fluid
                if ($fluid < 0) {
                    $fluid = $idx;
                    continue;
                } else {
                    throw new ExpectedException('Only one fluid column allowed!');
                }
            }

            throw new ExpectedException("unknown column format $col");
        }

        $alloc = $fixed;
        $remain = $this->max - $alloc;

        // second pass to handle percentages
        foreach ($columns as $idx => $col) {
            if (substr($col, -1) != '%') {
                continue;
            }
            $perc = floatval($col);

            $real = (int) floor(($perc * $remain) / 100);

            $columns[$idx] = $real;
            $alloc += $real;
        }

        $remain = $this->max - $alloc;
        if ($remain < 0) {
            throw new ExpectedException('Wanted column widths exceed available space');
        }

        // assign remaining space
        if ($fluid < 0) {
            $columns[$idx] += ($remain); // add to last column
        } else {
            $columns[$fluid] = $remain;
        }

        return $columns;
    }

    /**
     * Displays text in multiple word wrapped columns.
     *
     * @param int[]    $columns list of column widths (in characters, percent or '*')
     * @param string[] $texts   list of texts for each column
     * @param array    $colors  A list of color names to use for each column. use empty string for default
     *
     * @throws ExpectedException
     *
     * @return string
     */
    public function format($columns, $texts, $colors = [])
    {
        $columns = $this->calculateColLengths($columns);

        $wrapped = [];
        $maxlen = 0;

        foreach ($columns as $col => $width) {
            $wrapped[$col] = explode("\n", $this->wordwrap($texts[$col], $width, "\n", true));
            $len = count($wrapped[$col]);
            if ($len > $maxlen) {
                $maxlen = $len;
            }
        }

        $last = count($columns) - 1;
        $out = '';
        for ($i = 0; $i < $maxlen; $i++) {
            foreach ($columns as $col => $width) {
                if (isset($wrapped[$col][$i])) {
                    $val = $wrapped[$col][$i];
                } else {
                    $val = '';
                }
                $chunk = $this->pad($val, $width);
                if (isset($colors[$col]) && $colors[$col]) {
                    $chunk = $this->colors->apply($colors[$col], $chunk);
                }
                $out .= $chunk;

                // border
                if ($col != $last) {
                    $out .= $this->border;
                }
            }
            $out .= "\n";
        }

        return $out;
    }

    /**
     * Returns a well formatted table.
     *
     * @param array $tbody List of rows
     * @param array $thead List of columns
     *
     * Example:
     *
     *     +---------------+-----------------------+------------------+
     *     | ISBN          | Title                 | Author           |
     *     +---------------+-----------------------+------------------+
     *     | 99921-58-10-7 | Divine Comedy         | Dante Alighieri  |
     *     | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     *     | 960-425-059-0 | The Lord of the Rings | J. R. R. Tolkien |
     *     +---------------+-----------------------+------------------+
     */
    public function table(array $thead = [], array $tbody = [])
    {
        // All the rows in the table will be here until the end
        $table_rows = [];

        // We need only indexes and not keys
        if (!empty($thead)) {
            $table_rows[] = array_values($thead);
        }

        foreach ($tbody as $tr) {
            $table_rows[] = array_values($tr);
        }

        // Yes, it really is necessary to know this count
        $total_rows = count($table_rows);

        // Store all columns lengths
        // $all_cols_lengths[row][column] = length
        $all_cols_lengths = [];

        // Store maximum lengths by column
        // $max_cols_lengths[column] = length
        $max_cols_lengths = [];

        // Read row by row and define the longest columns
        for ($row = 0; $row < $total_rows; $row++) {
            $column = 0; // Current column index
            foreach ($table_rows[$row] as $col) {
                // Sets the size of this column in the current row
                $all_cols_lengths[$row][$column] = $this->strlen($col);

                // If the current column does not have a value among the larger ones
                // or the value of this is greater than the existing one
                // then, now, this assumes the maximum length
                if (!isset($max_cols_lengths[$column]) || $all_cols_lengths[$row][$column] > $max_cols_lengths[$column]) {
                    $max_cols_lengths[$column] = $all_cols_lengths[$row][$column];
                }

                // We can go check the size of the next column...
                $column++;
            }
        }

        // Read row by row and add spaces at the end of the columns
        // to match the exact column length
        for ($row = 0; $row < $total_rows; $row++) {
            $column = 0;
            foreach ($table_rows[$row] as $col) {
                $diff = $max_cols_lengths[$column] - $this->strlen($col);
                if ($diff) {
                    $table_rows[$row][$column] = $table_rows[$row][$column].str_repeat(' ', $diff);
                }
                $column++;
            }
        }

        $table = '';

        // Joins columns and append the well formatted rows to the table
        for ($row = 0; $row < $total_rows; $row++) {
            // Set the table border-top
            if ($row === 0) {
                $cols = '+';
                foreach ($table_rows[$row] as $col) {
                    $cols .= str_repeat('-', $this->strlen($col) + 2).'+';
                }
                $table .= $cols.PHP_EOL;
            }

            // Set the columns borders
            $table .= '| '.implode(' | ', $table_rows[$row]).' |'.PHP_EOL;

            // Set the thead and table borders-bottom
            if ($row === 0 && !empty($thead) || $row + 1 === $total_rows) {
                $table .= $cols.PHP_EOL;
            }
        }

        return $table;
    }

    /**
     * Pad the given string to the correct length.
     *
     * @param string $string
     * @param int    $len
     *
     * @return string
     */
    protected function pad($string, $len)
    {
        $strlen = $this->strlen($string);
        if ($strlen > $len) {
            return $string;
        }

        $pad = $len - $strlen;

        return $string.str_pad(' ', $pad, ' ');
    }

    /**
     * Measures char length in UTF-8 when possible.
     *
     * @param $string
     *
     * @return int
     */
    protected function strlen($string)
    {
        // don't count color codes
        $string = preg_replace("/\33\\[\\d+(;\\d+)?m/", '', $string);

        if (function_exists('mb_strlen')) {
            return mb_strlen($string);
        }

        return strlen($string);
    }

    /**
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    protected function substr($string, $start = 0, $length = null)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($string, $start, $length);
        } else {
            return substr($string, $start, $length);
        }
    }

    /**
     * Takes a string and writes it to the command line, wrapping to a maximum
     * width. If no maximum width is specified, will wrap to 75 max width.
     *
     * @param string $str
     * @param int    $width
     * @param string $break
     * @param bool   $cut
     *
     * @return string
     *
     * @see http://stackoverflow.com/a/4988494
     */
    public function wordwrap($str, $width = 75, $break = "\n", $cut = false)
    {
        $lines = explode($break, $str);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if ($this->strlen($line) <= $width) {
                continue;
            }
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if ($this->strlen($actual.$word) <= $width) {
                    $actual .= $word.' ';
                } else {
                    if ($actual != '') {
                        $line .= rtrim($actual).$break;
                    }
                    $actual = $word;
                    if ($cut) {
                        while ($this->strlen($actual) > $width) {
                            $line .= $this->substr($actual, 0, $width).$break;
                            $actual = $this->substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= trim($actual);
        }

        return implode($break, $lines);
    }

    /**
     * Returns the cell value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Gets number of colspan.
     *
     * @return int
     */
    public function getColspan()
    {
        return (int) $this->options['colspan'];
    }

    /**
     * Gets number of rowspan.
     *
     * @return int
     */
    public function getRowspan()
    {
        return (int) $this->options['rowspan'];
    }

    // Static Methods

    /**
     * Format Memory.
     *
     * @param mixed $memory
     *
     * @return string
     */
    public static function formatMemory($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d Bytes', $memory);
    }

    public static function formatTime($secs)
    {
        static $timeFormats = [
            [0, '< 1 second'],
            [1, '1 second'],
            [2, 'seconds', 1],
            [60, '1 minute'],
            [120, 'minutes', 60],
            [3600, '1 hour'],
            [7200, 'hours', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index == \count($timeFormats) - 1
                ) {
                    if (2 == \count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]).' '.$format[1];
                }
            }
        }
    }

    /**
     * @param mixed $val
     *
     * @return string
     */
    public function formatToString($val): string
    {
        if (null === $val) {
            return '(Null)';
        }

        if (\is_bool($val)) {
            return $val ? '(True)' : '(False)';
        }

        return (string) $val;
    }

    /**
     * Format Path.
     *
     * @param string $path
     * @param string $baseDir
     *
     * @return string
     */
    public static function formatPath(string $path, string $baseDir): string
    {
        return preg_replace('~^'.preg_quote($baseDir, '~').'~', '.', $path);
    }

    /**
     * Format FileSize.
     *
     * @param string $path
     *
     * @return string
     */
    public static function formatFileSize(string $path): string
    {
        if (is_file($path)) {
            $size = filesize($path) ?: 0;
        } else {
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS)) as $file) {
                $size += $file->getSize();
            }
        }

        return self::formatMemory($size);
    }
}
