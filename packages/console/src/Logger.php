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

use Psr\Log\LoggerInterface;

/**
 * Class Logger.
 *
 * The same as CLI, but implements the PSR-3 logger interface
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
abstract class Logger extends Command implements LoggerInterface
{
}
