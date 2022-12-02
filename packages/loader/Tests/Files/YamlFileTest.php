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

use BiuradPHP\Loader\Files\Adapters\YamlFIleAdapter;
use BiuradPHP\Loader\Locators\ConfigLocator;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class YamlFileTest extends TestCase
{
    public function testFromString(): void
    {
        $yaml = new YamlFileAdapter();
        $this->assertEquals($this->getExpectedData(), $yaml->fromString($this->getActualData()));
    }

    public function testFromFile(): void
    {
        $yaml = new YamlFileAdapter();
        $this->assertEquals($this->getExpectedData(), $yaml->fromFile(__DIR__ . '/Fixtures/data/test2.yaml'));

        $loader = new ConfigLocator();
        $this->assertEquals($this->getExpectedData(), $loader->loadFile(__DIR__ . '/Fixtures/data/test2.yaml'));
    }

    public function testDumpYamlToArray(): void
    {
        $yaml     = new YamlFileAdapter();
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
}
