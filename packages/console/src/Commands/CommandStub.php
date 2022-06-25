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

use BiuradPHP\Toolbox\ConsoleLite\Stuble\StubGenerator;

class CommandStub extends StubGenerator
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'stuble 
    {--command=::Enter a command name of your choice in "coomand:name" pattern.}
    {--force::Forces the class to be overwritten}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Php File out of a stub';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Example command';

    /**
     * Set the namespace.
     *
     * @var string
     */
    protected $namespace = 'App\\Console\\';

    /**
     * Set the class.
     *
     * @var string
     */
    protected $class = 'WorldClass';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../resources/stubs/example.stub';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('generate:command', $this->hasOption('command') ?: 'command:name', $stub);
    }
}
