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

namespace BiuradPHP\Toolbox\ConsoleLite\Compile;

use BiuradPHP\Toolbox\ConsoleLite\Terminal;

/**
 * The ConsoloLite Phar Config.
 *
 * This is a utility class to generate a PHAR.
 *
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class PharConfig extends Terminal
{
    public function __construct()
    {
        parent::__construct();

        //$this->validate();
    }
}
