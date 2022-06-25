> This library was strongly inspired by [Symfony Console](https://symfony.com/doc/current/components/console.html)

<div id="autoloader-logo" align="center">
    <h1 style="font-weight:bold">Console Lite - BiuradPHP Toolbox</h1>
    <br />
    <img src="https://raw.githubusercontent.com/biurad/Console-lite/master/logo.png" alt="Autoloader Jet Logo" height="200px" width="400px"/>
    <h3>This is the light weight version of Symfony Console. @author Divine Niiquaye.</h3>
</div>

<div id="badges" align="center">

[![Latest Stable Version](https://poser.pugx.org/biurad/consolelite/v/stable)](https://packagist.org/packages/biurad/consolelite)
[![Build Status](https://travis-ci.org/biurad/Console-lite.svg?branch=master)](https://travis-ci.org/biurad/Console-lite)
[![Total Downloads](https://poser.pugx.org/biurad/consolelite/downloads)](https://packagist.org/packages/biurad/consolelite)
![GitHub issues](https://img.shields.io/github/issues/biurad/console-lite.svg)
[![StyleCI](https://github.styleci.io/repos/186709012/shield?branch=master)](https://github.styleci.io/repos/186709012)
[![BCH compliance](https://bettercodehub.com/edge/badge/biurad/Autoloader?branch=master)](https://bettercodehub.com/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e08ae4d55074443f8dd4fd96042c36e0)](https://app.codacy.com/app/biustudio/Console-lite?utm_source=github.com&utm_medium=referral&utm_content=biurad/Console-lite&utm_campaign=Badge_Grade_Dashboard)
[![License](https://poser.pugx.org/biurad/consolelite/license)](https://packagist.org/packages/biurad/consolelite)

</div>

The Console tool allows you to create command-line commands. Your console commands can be used for any recurring task, stub generator, phar compile, such as cronjobs, imports, or other batch jobs.

# Installation

Just run this composer command:

```bash
composer require biurad/consolelite
```

# Quickstart

## Creating a Console Application

First, you need to create a PHP script to define the console application:

```php
#!/usr/bin/env php
<?php
// application.php
use BiuradPHP\Toolbox\ConsoleLite\Application;

require __DIR__.'/vendor/autoload.php';

$application = new Application();

// ... register commands

$application->run();
```

Console Lite has a totally different approach in building console commands, not similar to Symfony Console but similar to Laravel Artisan. This was done in order to make it light weight.

You can register the commands using two different ways:

1. ```php
   // ...
   $application->register(new GenerateCommand());
    ```

2. ```php
   // ...
   $application->command('hello', 'Enter your name to start', function () {
        $this->writeln('Hello World');
   });

## Console Command Example

### This example is to print out a name without creating a class file

```php
#!/usr/bin/env php
<?php

use BiuradPHP\Toolbox\ConsoleLite\Application;

require __DIR__.'/vendor/autoload.php';

// 1. Initialize app
$application = new Application;

// 2. Register commands
$application->command('hello {name}', 'Enter your name', function($name) {
    $this->writeln("Hello {$name}");
});

// 3. Run app
$application->run();
```

### This example is to print out a name creating a class file

```php
<?php

use BiuradPHP\Toolbox\ConsoleLite\Command;

// create a NameCommand.php
class NameCommand extends Command
{
    /** @param string   The Application Name */
    protected $app          =   'Name Command';

    /** @param string   Your Set Command */
    protected $signature    =   'hello {name}';

    /** @param string   Your Command Description */
    protected $description  =   'Enter your name';

    /**
    * The Command Handler
    *
    * @param string $name   The Name input value.
    *
    * @return void
    */
    public function handle($name)
    {
        return $this->writeln("Hello {$name}");
    }
}
```

After that you create a filename without any path or file extention:

example, create a **console** `file` without file extention.

```php
#!/usr/bin/env php
<?php

use BiuradPHP\Toolbox\ConsoleLite\Application;
use NameCommand;

require __DIR__.'/vendor/autoload.php';

// 1. Initialize app
$application = new Application;

// 2. Register commands
$application->register(new NameCommand);

// 3. Run app
$application->run();
```

### Show Help

You can show help by putting `--help` or `-h` for each command. For example:

```bash
php console hello --help
```

### Enable Color

You can force colors by putting `--ansi` for each command. For example:

```bash
php console hello --ansi
```

### Disable Color

You can disable color by putting `--no-ansi` for each command. For example:

```bash
php console hello --no-ansi
```

# Command Usage and Options

The command has a powerful property called signature, this contains all the commands, options and arguements.

## The basic usage is simple

- Create a `class` and extend it to `BiuradPHP\Toolbox\ConsoleLite\Command`.

- Or use the method `command` from `BiuradPHP\Toolbox\ConsoleLite\Application`.

- Implement the `properties` method and register options, arguments, commands and set help texts

  - Implementing decription to command, add the following to the protected property $signature or method `command` of Application class.
  
    ```php
    protected $description = 'Enter a description'; // add a general description.
      ```

    ```php
    <?php
    // application.php
    use BiuradPHP\Toolbox\ConsoleLite\Application;

    require __DIR__.'/vendor/autoload.php';

    $app = Application;
    $app->command('hello', 'This is a description'/** add a general description to the second parameter. */, function () {
        $this->writeln('Hello World');
    });
      ```

  - Implementing add command, the protected proterty $signature holds the command, same applies to Application method `command`.
  
    ```php
    protected $signature = 'hello'; // add a command.
      ```

  - Implementing options, add the following to the protected property $signature.
  
      ```php
    protected $signature = 'hello {--option} {--anotheroption}'; // the '--' represents an option.
      ```

  - Implementing options has an input, add the following to the protected property $signature.
  
      ```php
    protected $signature = 'hello {--option=} {--anotheroption=}'; // the '=' represents an option has an input.
      ```

  - Implementing arguements, add the following to the protected property $signature.
  
      ```php
    protected $signature = 'hello {arguement} {anotherarguement}'; // this represents an argument.
      ```

  - Implementing description for options and arguements, add the following to the protected property $signature.
  
      ```php
    protected $signature = 'hello {arguement::Description} {--option::Description} {--option=::Description}'; // the '::' represents a description.
      ```

- > NB: This applies to `command` method in Application class.

- Implement the `handle` method and do your business logic there.

  - Open the file Command.php in folder `src` and find out the methods to use from there.

## Exceptions

By default the CLI classes registers two error or exception handlers.

- Application Exception
   To use the Application Exception, use example:

   ```php
    #!/usr/bin/env php
    <?php

    use BiuradPHP\Toolbox\ConsoleLite\Application;
    use BiuradPHP\Toolbox\ConsoleLite\Exception\ConsoleLiteException;
    use BiuradPHP\Toolbox\ConsoleLite\Exception\DeprecatedException;

    require __DIR__.'/vendor/autoload.php';

    $application = new Application;

    $application->command('exception {--test} {--error} {--replace}', 'This is an exception test', function () {
        // This throws an application exception.
        if (! $this->hasOption('test')) {
            throw new ConsoleLiteException('Test option not allowed');
        }

        // This throws a deprecated exception.
        if ($this->hasOption('error')) {
            throw new DeprecatedException(['--error', '--replace']);
        }

        $this->block('This is an Exception test');
    });

    $application->run();
   ```

## Colored output

Colored output is handled through the `Colors` class. It tries to detect if a color terminal is available and only
then uses terminal colors. You can always suppress colored output by passing ``--no-ansi`` to your scripts.

Simple colored messages can be printed by you using the convinence methods `writeln()`, `write()`, `sucessBlock()`, `style()`,
`errorBlock()`, and `block()`. Each of this methods contains three parameters, one for message, two for styles including forground color, background color, and options.

Within the second parameters of the above mentioned methods or functions for writing raw or colored text unto the output. forground color, background color or options, could be defined as string or in array. backgorund colors begins with a '**bg_**' and colors are writen as they are.

e.g. to write background color only

```php
$this->writeln('Message to output', 'bg_yellow');
```

e.g. to write forground color only

```php
$this->writeln('Message to output', 'yellow');
```

e.g. to write options only

```php
$this->writeln('Message to output', 'italic');
```

e.g. to write all styles combined

```php
$this->writeln('Message to output', ['bg_yellow', 'green', 'bold']);
```

The formatter allows coloring full columns. To use that mechanism pass an array of colors as third parameter to
its `format()` method. Please note that you can not pass colored texts in the second parameters (text length calculation
and wrapping will fail, breaking your texts).

## Formatter

The `Formatter` class allows you to align texts in multiple columns. It tries to figure out the available
terminal width on its own. It can be overwritten by setting a `COLUMNS` environment variable.

The formatter is used through the `format()` method which expects at least two arrays: The first defines the column
widths, the second contains the texts to fill into the columns. Between each column a border is printed (a single space
by default).

The formatter contains other useful methods used to format time, memory, file paths and more.

Columns width can be given in three forms:

- fixed width in characters by providing an integer (eg. ``15``)
- precentages by provifing an integer and a percent sign (eg. ``25%``)
- a single fluid "rest" column marked with an asterisk (eg. ``*``)

When mixing fixed and percentage widths, percentages refer to the remaining space after all fixed columns have been
assigned.

Space for borders is automatically calculated. It is recommended to always have some relative (percentage) or a fluid
column to adjust for different terminal widths.

The formatter is used for the automatic help screen accessible when calling your script with ``-h`` or ``--help``.

## Compiling to Phar

ConsoleLite has function were you could compile your php source-codes into a phar file.
You dont need to create a Phar class, ConsoleLite has it taken care of.

if you installed ConsoleLite as a composer dependency in your project, then hit

```bash
php vendor/bin/clite compile --config
```

This generates a sample clite.json file, looking something like the one below

```json
{
    "application": "ConsoleLite Configuration",
    "generated": "2019/06/17 03:41",
    "version": "1.0.0",
    "stuble": {
        "config": {
            "name": "ConsoleLite Stub Generator"
        }
    },
    "compile": {
        "config": {
            "name": "clite.phar",
            "index": "docs/tests/test",
            "compression": 0,
            "signature": null
        },
        "pack": {
            "autoload": "vendor/autoload.php",
            "bin": "docs/tests/test",
            "files": [
                "clite.json",
                "composer.json"
            ],
            "directory": [
                "src/",
                "vendor/"
            ],
            "excludes": [
                "Tests/*",
                "!*.php"
            ]
        },
        "unpack": {
            "extract-to": "unpack",
            "files": null,
            "overwrite": true
        }
    }
}
```

The `compile` command has options for two kinds of input, thus the **pack** and **unpack**. Given them different config settings in clite.json.

If you already know the Phar class in php. you can finish up the steps by settings them up in clite.json.
The phar packs or reads directories and files from your working directory.

The **pack** input after command `compile` generate a phar file using the clite.json or using the options below.

- --signature -> Set the signing signature of the phar. eg openssl.key.

- --index -> Set the main file were the phar will be looaded from.

- --compression -> Set the compression level for the phar. eg 0 or 4096.

- --directory -> Set the directory where the phar is generated from.

- --version -> Set the version of the project, so you stay up to date.

- --bin -> Set the bin to be added to the compiled phar file.

- --files -> Add the files that will be needed example, LICENSE file.

- --excludes -> Add the excluded files or directories from the compiled phar file.

- --type -> Set the type of phar that should be generated cli or web.

- --autoload -> Add the file which contains all the classes and namespaces for autoloading.

The **unpack** input after the command `compile` extracts exverything containing in the phar file, into a directory reading from the clite.json or
using the options.

- --files -> List the files that will be needed example, LICENSE file.(optional)

- --extract -> Set the folder where the files and directories in phar will be extracted to.

- --overwrite -> Allow the previous extracted folders to be overwritten.

## Stuble

Stuble is command line tool built with PHP to simplify working with stubs.
Stuble will collect parameters in your stub(s) file(s) and ask you those parameters.
So you don't need to write scripts to handle each stub file.

This is a class that simple let's you generate a class or php file out of a template.
This is an abstract class, so you don't need to use it like phar, but use it to create a stub.

For usuage, extends the your stuble class to `StubleGenerator`.
The StubleGenerator has a similarity to Laravel `GeneratorCommand`, cause part of the class is from Laravel, but made simple. Check the class to find out.

When working with ConsoleLite Stuble, you don't need to input a destination path. Path deternation are generated from your namespaces and then the class in inserted into the the namespaces folders, starting from your working directory.

This code below is an example of how to implement a stuble.

```php
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
```

and the template file which was saved in `.stub` file extention, looks like the one below

```php
<?php

namespace GenerateNamespace;

use BiuradPHP\Toolbox\ConsoleLite\Command;

class GenerateClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        //code...
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //code...
    }
}
```

## PSR-3 Logging

The CLI class is a fully PSR-3 compatible logger (printing colored log data to STDOUT and STDERR). This is useful when
you call backend code from your CLI that expects a Logger instance to produce any sensible status output while running.

By default the logger functions are written in `Command` class.

To use this ability simply inherit from `BiuradPHP\Toolbox\ConsoleLite\PSR3` instead of `BiuradPHP\Toolbox\ConsoleLite\Command`, then pass `$this`
as the logger instance. Be sure you have the suggested `psr/log` composer package installed.

# License

- [MIT](LICENSE)
- [Divine Niiquaye](https://instagram.com/legendborn_gh)
