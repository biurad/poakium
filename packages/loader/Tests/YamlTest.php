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
use BiuradPHP\Loader\Files\Adapters\YamlAdapter;
use BiuradPHP\Loader\Files\ConfigLoader;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class YamlTest extends TestCase
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
        return <<<YAML
test:
    dir:
        root: true
        lib: ~
    forbidden_file_extensions:
        - php
        - php3
        - pl
        - com
        - exe
        - bat
        - cgi
        - htaccess
    debugger_token: debug

YAML;
    }

    public function testFromString()
    {
        $yaml = new YamlAdapter();
        $this->assertEquals($this->getExpectedData(), $yaml->fromString($this->getActualData()));
    }

    public function testFromFile()
    {
        $yaml = new YamlAdapter();
        $this->assertEquals($this->getExpectedData(), $yaml->fromFile(__DIR__.'/Fixtures/data/test2.yaml'));

        $loader = new ConfigLoader();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__.'/Fixtures/data/test2.yaml'));
    }

    public function testDumpYamlToArray()
    {
        $yaml = new YamlAdapter();
        $expected = <<<YAML
---
test:
  dir:
    root: true
    lib: ~
  forbidden_file_extensions:
  - php
  - php3
  - pl
  - com
  - exe
  - bat
  - cgi
  - htaccess
  debugger_token: debug
...

YAML;
        $this->assertEquals($expected, $yaml->dump($this->getExpectedData()));
    }
}

