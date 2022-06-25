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

namespace BiuradPHP\Toolbox\ConsoleLite\Commands;

use BiuradPHP\Toolbox\ConsoleLite\Command;

/**
 * ConsoleLite Default Command.
 *
 * This class is a fallback or callback command
 * incase no command is non-availabe.
 * This command can be overrided by using the method
 *
 * $this->setDefaultCommand
 *
 * @see setDefaultCommand in Application class.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class CommandList extends Command
{
    protected $signature = 'list {keyword?::This finds a not completed command}';

    protected $description = 'Show available commands';

    public function handle($keyword)
    {
        $count = 0;
        $maxLen = 0;

        if ($keyword) {
            $commands = $this->getCommandsLike($keyword);

            $this->getColors()->isEnabled() ?
            $this->block("Here are commands like '{$keyword}': ", ['bg_magenta', 'white', 'bold']) :
            $this->writeln("Here are commands like '{$keyword}': ").$this->newLine();
        } else {
            $commands = $this->getRegisteredCommands();

            if ('ConsoleLite Application - Version' === $this->getName()) {
                $this->isLinux() ?
                    $this->robot('ConsoleLite is been runned in Linux environment') :
                $this->isWindows() ?
                    $this->robot('ConsoleLite is been runned in Windows environment') :
                $this->robot(sprintf('ConsoleLite is been runned in %s environment', PHP_OS));
            }

            $this->writeln('Usuage:', 'purple');
            $this->newLine();
            $this->write(' '.$this->getFilename());
            $this->writeln(' [options] [arguements]');
            $this->newLine();

            $this->writeln('Default Options:', 'purple');
            $this->newLine();
            $displayOpts = [
                '-h, --help'  => 'Displays help and usage info for a command',
                '-v, --debug' => 'Displays the hidden debug messages',
                '--ansi'      => 'Forces color to display on terminal',
                '--no-ansi'   => 'Removes colors from the output on terminal',
            ];
            $this->helpblock($displayOpts);

            $this->writeln('Available Commands: ', 'purple');
            $this->newLine();
        }

        ksort($commands);

        foreach (array_keys($commands) as $name) {
            if (strlen($name) > $maxLen) {
                $maxLen = strlen($name);
            }
        }
        $pad = $maxLen + 3;

        foreach ($commands as $name => $command) {
            // if no commands matched or we just matched namespaces
            $expr = preg_replace_callback('{([^:]+|)}', function ($matches) {
                return preg_quote($matches[1]).'[^:]*';
            }, $name);
            if (\count(preg_grep('{^'.$expr.'$}i', $commands)) < 1) {
                if (false !== $pos = strrpos($name, ':')) {
                    // check if a namespace exists and contains commands
                    $namespaced = substr($name, 0, $pos);
                    $this->writeln(str_repeat(' ', 1).$namespaced);
                }
            }
            $no = ++$count.') ';
            $this->write(str_repeat(' ', 4 - strlen($no)).$this->style($no, 'dark_gray'));
            $this->write($this->style($name, 'green').str_repeat(' ', $pad - strlen($name)));
            $this->writeShort($command['description']);
            $this->newLine();
        }

        if ($this->isVerbose()) {
            $this->hasColorSupport() ? $this->errorBlock('You are in Debug mode') : $this->debug('You are in Debug mode');
        }
    }
}
