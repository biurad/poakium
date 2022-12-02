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

use BiuradPHP\Loader\Files\Adapters\JsonFileAdapter;
use BiuradPHP\Loader\Locators\ConfigLocator;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class JsonFIleTest extends TestCase
{
    public function testFromString(): void
    {
        $json = new JsonFileAdapter();
        $this->assertEquals($this->getExpectedData(), $json->fromString($this->getActualData()));
    }

    public function testFromFile(): void
    {
        $json = new JsonFileAdapter();
        $this->assertEquals($this->getExpectedData(), $json->fromFile(__DIR__ . '/Fixtures/data/test3.json'));

        $loader = new ConfigLocator();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__ . '/Fixtures/data/test3.json'));
    }

    public function testDumpJsonToArray(): void
    {
        $json = new JsonFileAdapter();
        $this->assertEquals($this->getActualData(), $json->dump($this->getExpectedData()));
        $this->assertJsonStringEqualsJsonString($this->getActualData(), $json->dump($this->getExpectedData()));
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
        return <<<JSON
{
    "test": {
        "dir": {
            "root": true,
            "lib": null
        },
        "forbidden_file_extensions": [
            "php",
            "php3",
            "pl",
            "com",
            "exe",
            "bat",
            "cgi",
            "htaccess"
        ],
        "debugger_token": "debug"
    }
}
JSON;
    }
}
