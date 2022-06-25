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

namespace BiuradPHP\Toolbox\ConsoleLiteTest;

use BiuradPHP\Toolbox\ConsoleLite\Application;
use BiuradPHP\Toolbox\ConsoleLiteTest\fixtures\FooCommand;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testSetGetName()
    {
        $application = new Application();
        $application->setName('foo');

        $this->assertEquals('foo', $application->getName(), '->setName() sets the name of the application');
    }

    public function testSetGetVersion()
    {
        $application = new Application();
        $application->setVersion('bar');

        $this->assertEquals('bar', $application->getVersion(), '->setVersion() sets the version of the application');
    }

    public function testRegister()
    {
        $application = new Application();
        $application->register(new FooCommand());
        $command = $application->hasCommand('foo:bar1') ? 'foo:bar1' : null;

        $this->assertEquals('foo:bar1', $command, '->register() registers a new command');
    }

    /**
     * @requires function posix_isatty
     */
    public function testCanCheckIfTerminalIsInteractive()
    {
        $application = new Application();
        $tester = $application;
        $tester->run();
        $inputStream = $tester->openOutputStream();

        $this->assertEquals($tester->isInteractive($inputStream), @posix_isatty($inputStream));
    }

    public function testDebug()
    {
        $application = new Application();
        $output = $application;

        $this->assertEquals($output->verbose, $output->isVerbose(), 'test the verbosity as its output');
    }

    public function testSetIsDecorated()
    {
        $output = new Application();
        $output->getColors()->enable();

        $this->assertTrue($output->getColors()->isEnabled(), 'setDecorated() sets the decorated flag --ansi');
    }

    public function testDisableDecorated()
    {
        $output = new Application();
        $output->getColors()->disable();

        $this->assertFalse($output->getColors()->isEnabled(), 'disableDecorated() sets the un-decorated flag --no-ansi');
    }
}
