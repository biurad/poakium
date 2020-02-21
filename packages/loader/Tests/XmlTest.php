<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/loadermanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Tests;

use PHPUnit\Framework\TestCase;
use BiuradPHP\Loader\Adapters\XmlAdapter;
use BiuradPHP\Loader\ConfigLoader;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class XmlTest extends TestCase
{
    private function getExpectedData()
    {
        return [
            'test' => [
            'dir' => [
                'root' => true,
                'lib' => null,
            ],
            'forbidden_file_extensions' => [
                'php',
                'php3',
                'pl',
                'com',
                'exe',
                'bat',
                'cgi',
                'htaccess'
            ],
            'debugger_token' => 'debug',
            ]
        ];
    }

    private function getActualData(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loader-config>
    <test>
        <dir>
            <root>1</root>
            <lib></lib>
        </dir>
        <forbidden_file_extensions>php</forbidden_file_extensions>
        <forbidden_file_extensions>php3</forbidden_file_extensions>
        <forbidden_file_extensions>pl</forbidden_file_extensions>
        <forbidden_file_extensions>com</forbidden_file_extensions>
        <forbidden_file_extensions>exe</forbidden_file_extensions>
        <forbidden_file_extensions>bat</forbidden_file_extensions>
        <forbidden_file_extensions>cgi</forbidden_file_extensions>
        <forbidden_file_extensions>htaccess</forbidden_file_extensions>
        <debugger_token>debug</debugger_token>
    </test>
</loader-config>

XML;
    }

    public function testFromString()
    {
        $xml = new XmlAdapter();
        $this->assertEquals($this->getExpectedData(), $xml->fromString($this->getActualData()));
    }

    public function testFromFile()
    {
        $xml = new XmlAdapter();
        $this->assertEquals($this->getExpectedData(), $xml->fromFile(__DIR__.'/Fixtures/data/test5.xml'));

        $loader = new ConfigLoader();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__.'/Fixtures/data/test5.xml'));
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/data/test5.xml', $this->getActualData());
    }

    public function testDumpXmlToArray()
    {
        $xml = new XmlAdapter();
        $this->assertEquals($this->getActualData(), $xml->dump($this->getExpectedData()));
        $this->assertXmlStringEqualsXmlString($this->getActualData(), $xml->dump($this->getExpectedData()));
    }
}

