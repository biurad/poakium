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
use BiuradPHP\Loader\Adapters\JsonAdapter;
use BiuradPHP\Loader\ConfigLoader;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class JsonTest extends TestCase
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

    public function testFromString()
    {
        $json = new JsonAdapter();
        $this->assertEquals($this->getExpectedData(), $json->fromString($this->getActualData()));
    }

    public function testFromFile()
    {
        $json = new JsonAdapter();
        $this->assertEquals($this->getExpectedData(), $json->fromFile(__DIR__.'/Fixtures/data/test3.json'));

        $loader = new ConfigLoader();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__.'/Fixtures/data/test3.json'));
    }

    public function testDumpJsonToArray()
    {
        $json = new JsonAdapter();
        $this->assertEquals($this->getActualData(), $json->dump($this->getExpectedData()));
        $this->assertJsonStringEqualsJsonString($this->getActualData(), $json->dump($this->getExpectedData()));
    }
}

