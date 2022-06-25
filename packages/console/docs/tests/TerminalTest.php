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

use BiuradPHP\Toolbox\ConsoleLite\Terminal;
use PHPUnit\Framework\TestCase;

class TerminalTest extends TestCase
{
    private $colSize;
    private $lineSize;

    protected function setUp()
    {
        $this->colSize = getenv('COLUMNS');
        $this->lineSize = getenv('LINES');
    }

    protected function tearDown()
    {
        putenv($this->colSize ? 'COLUMNS='.$this->colSize : 'COLUMNS');
        putenv($this->lineSize ? 'LINES' : 'LINES='.$this->lineSize);
    }

    public function test()
    {
        putenv('COLUMNS=100');
        putenv('LINES=50');
        $terminal = new Terminal();
        $this->assertSame(100, $terminal->getWidth());
        $this->assertSame(50, $terminal->getHeight());

        putenv('COLUMNS=120');
        putenv('LINES=60');
        $terminal = new Terminal();
        $this->assertSame(120, $terminal->getWidth());
        $this->assertSame(60, $terminal->getHeight());
    }

    public function testZeroValues()
    {
        putenv('COLUMNS=0');
        putenv('LINES=0');

        $terminal = new Terminal();

        $this->assertSame(0, $terminal->getWidth());
        $this->assertSame(0, $terminal->getHeight());
    }

    public function testCliteLoad()
    {
        $terminal = new Terminal();
        $file = __DIR__.'/fixtures/clite.json';

        $expected = $terminal->loadFile($file);
        $actual = $terminal->decodeFile($file);

        $this->assertSame($expected, $actual);
    }
}
