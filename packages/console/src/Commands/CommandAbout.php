<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command newLine.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\ConsoleLite\Commands;

use BiuradPHP\Toolbox\ConsoleLite\Command;
use BiuradPHP\Toolbox\ConsoleLite\Formatter;

/**
 * The ConsoleLite About Info.
 *
 * This class is a brief about info of consolelite
 * and the system consolelite is been runned in.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class CommandAbout extends Command
{
    protected $signature = 'about';

    protected $description = 'Displays information about the current project';

    protected $formatter;

    public function __construct()
    {
        $this->formatter = new Formatter();
    }

    public function handle()
    {
        $this->robot('ConsoleLite is brief info');
        $this->formatter->setBorder(' | '); //set border
        $this->getTerminal()->setMaxWidth('100');
        $this->newLine(1, true);

        // set the header option
        $this->write(
            $this->formatter->format(
                ['20%', '*'],
                ['Console Lite', 'Description'],
                [['bold', 'green'], 'light_cyan']
            )
        );
        $this->newLine(1, true);

        // list of first ([0]name, [1]note or description)
        $header_section = [
            ['About', 'See the ReadMe.md for more info'],
            ['Version', CONSOLELITE_VERSION.' latest built'],
            ['Copyright', 'Divine Niiquaye hello@biuhub.net'],
        ];
        foreach ($header_section as $first) {
            $this->write(
                $this->formatter->format(
                    ['20%', '*'],
                    [$first[0], $first[1]],
                    ['yellow', 'none']
                )
            );
        }
        // create a horizontal line
        $this->newLine(1, true);

        // set the header option
        $this->write(
            $this->formatter->format(
                ['20%', '*'],
                ['App Check', 'Description'],
                [['bold', 'green'], 'light_cyan']
            )
        );

        // create a horinzontal line
        $this->newLine(1, true);

        //set options and settings
        $memory = $this->getSilencer()->call('memory_get_usage');

        // list of second ([0]name, [1]note or description)
        $app_section = [
            ['Charset', 'UTF-8'],
            ['File InUse', $this->getFilename()],
            ['Memory Usage', $this->formatter->formatMemory($memory)],
            ['PHP OS', $this->isWindows() ? 'Windows'.' Architecture -> '.(PHP_INT_SIZE * 8).' bits' : PHP_OS],
            ['PHP Version', phpversion() > 7.2 ? PHP_VERSION : 'Upgrade your php version to 7.2 and above'],
            ['Intl locale', class_exists('Locale', false) && \Locale::getDefault() ? \Locale::getDefault() : 'n/a'],
            ['Timezone', date_default_timezone_get().' ('.(new \DateTime())->format(\DateTime::W3C).')'],
            ['MBString PHP EXT', \extension_loaded('mbstring') ? 'true' : 'false'],
            ['Mcrypt PHP Func', function_exists('mcrypt_decrypt') && function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt') && function_exists('mcrypt_encrypt') ? 'exists' : 'not found'],
            ['JSon PHP EXT', \extension_loaded('json') ? 'true' : 'false'],
            ['OPcache PHP EXT', \extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'],
            ['APCu PHP EXT', \extension_loaded('apcu') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'],
            ['Xdebug PHP EXT', \extension_loaded('xdebug') ? 'true' : 'false'],
        ];
        foreach ($app_section as $second) {
            $this->write(
                $this->formatter->format(
                    ['20%', '*'],
                    [$second[0], $second[1]],
                    ['yellow', 'none']
                )
            );
        }

        // create a horizontal line
        $this->newLine(1, true);
    }
}
