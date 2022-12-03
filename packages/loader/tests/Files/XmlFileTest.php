<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Loader\Tests;

use BiuradPHP\Loader\Files\Adapters\XmlFileAdapter;
use BiuradPHP\Loader\Locators\ConfigLocator;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class XmlFIleTest extends TestCase
{
    public function testFromString(): void
    {
        $xml = new XmlFileAdapter();
        $this->assertEquals($this->getExpectedData(), $xml->fromString($this->getActualData()));
    }

    public function testFromFile(): void
    {
        $xml = new XmlFileAdapter();
        $this->assertEquals($this->getExpectedData(), $xml->fromFile(__DIR__ . '/Fixtures/data/test5.xml'));

        $loader = new ConfigLocator();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__ . '/Fixtures/data/test5.xml'));
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/data/test5.xml', $this->getActualData());
    }

    public function testDumpXmlToArray(): void
    {
        $xml = new XmlFileAdapter();
        $this->assertEquals($this->getActualData(), $xml->dump($this->getExpectedData()));
        $this->assertXmlStringEqualsXmlString($this->getActualData(), $xml->dump($this->getExpectedData()));
    }

    private function getExpectedData()
    {
        return [
            'test' => [
                'dir' => [
                    'root' => true,
                    'lib'  => null,
                ],
                'forbidden_file_extensions' => [
                    'php',
                    'php3',
                    'pl',
                    'com',
                    'exe',
                    'bat',
                    'cgi',
                    'htaccess',
                ],
                'debugger_token' => 'debug',
            ],
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
}
