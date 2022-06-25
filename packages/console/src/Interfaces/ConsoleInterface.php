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

namespace BiuradPHP\Toolbox\ConsoleLite\Interfaces;

interface ConsoleInterface
{
    /**
     * Register a closure route command.
     *
     * @param string   $signature   command signature
     * @param string   $description command description
     * @param \Closure $handler     command handler
     */
    public function command(string $signature, string $description, \Closure $handler);

    /**
     * Register a command.
     *
     * Regsiter the class where the command was created extended
     * to Command class.
     *
     * @param \BiuradPHP\Toolbox\ConsoleLite\Command $command
     */
    public function register(Command $command);

    /**
     * This excutes a command.
     *
     * Excute a command whether a .php file, .bat file, plain file or a command.
     *
     * @param string $command
     */
    public function execute(string $command);

    /**
     * This runs the application.
     */
    public function run();
}
