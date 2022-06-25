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

namespace BiuradPHP\Toolbox\ConsoleLite\Stuble;

use BiuradPHP\Toolbox\ConsoleLite\Command;

/**
 * ConsoleLite Stub Generator.
 *
 * This class is used to generate stubs
 * for a specified class.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
abstract class StubGenerator extends Command
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type;

    /**
     * The namespace of a class to set.
     *
     * @var string
     */
    protected $namespace;

    /**
     * SThe class name to set.
     *
     * @var string
     */
    protected $class;

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    abstract protected function getStub();

    /**
     * Execute the console command.
     *
     * @throws \BiuradPHP\Toolbox\FilePHP\FileException
     *
     * @return bool|null
     */
    public function handle()
    {
        $path = getcwd().DIRECTORY_SEPARATOR.str_replace('\\', '/', $this->namespace);

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->getFileHandler()->getInstance($path)->mkdir();

        $classfile = $path.$this->getPath($this->class);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((!$this->hasOption('force') || !$this->getOption('force')) &&
             $this->alreadyExists($classfile)) {
            return $this->getColors()->isEnabled() ?
                $this->errorBlock($this->type.' already exists!') :
                $this->writeln($this->type.' already exists!');
        }

        $this->getFileHandler()->getInstance($classfile)->put($this->buildClass($this->namespace, $this->class));

        $this->getColors()->isEnabled() ?
            $this->successBlock($this->type.' created successfully.') :
            $this->writeln($this->type.' created successfully.');
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     *
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return $this->getFileHandler()->getInstance($rawName)->exists();
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        return '/'.trim($name, '\\/').'.php';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $namespace
     * @param string $class
     *
     * @throws \BiuradPHP\Toolbox\FilePHP\FileException
     *
     * @return string
     */
    protected function buildClass($namespace, $class)
    {
        $stub = $this->getFileHandler()->getInstance($this->getStub())->get();

        return $this->replaceNamespace($stub, $namespace)->replaceClass($stub, $class);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace('GenerateNamespace', $this->getNamespace($name), $stub);

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
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
        $class = str_replace('\\', '', $name);

        return str_replace('GenerateClass', $class, $stub);
    }
}
