<?php

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

use BiuradPHP\Loader\DataLoader;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class DataTest extends TestCase
{
    public function testGetData()
    {
        $data = [
            'config' => [
                'dir' => [
                    'root' => __DIR__,
                    'lib' => __DIR__.'/lib/',
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
            'runtime' => [
                'company_id' => 1,
            ],
            'settings' => [
                'Company' => [
                    'logo' => true,
                    'company_name' => 'Simtech',
                ],
                'Image_verification' => [
                    'use_for' => [
                        'register' => 'Y',
                        'form_builder' => 'Y',
                    ],
                ],
            ],
        ];

        $config = new DataLoader($data);
        $config->setWritable();
        $test = 'Test';
        $config->test = [];
        $config->test->one = $test;
        $config->test->two = $test;
        $config->test->three = $test;
        $config->test->four = $test;
        $config->test->six = $test;

        $this->assertSame('Test', $config->get('test.one'));
        $this->assertSame('Test', $config->get('test.two'));
        $this->assertSame('Test', $config->get('test.three'));
        $this->assertSame('Test', $config->get('test.four'));
        $this->assertSame('Test', $config->get('test.six'));

        $this->assertEquals($data['config']['dir']['root'], $config->get('config.dir.root'));
        $this->assertEquals($data['config']['dir']['lib'], $config->get('config.dir.lib'));
        $this->assertisArray($data['config']['forbidden_file_extensions']);
        $this->assertisObject($config->get('config.forbidden_file_extensions'));
        $this->assertEquals($data['config']['debugger_token'], $config->get('config.debugger_token'));
        $this->assertEquals($data['runtime']['company_id'], $config->get('runtime.company_id'));
        $this->assertisArray($data['settings']['Company']);
        $this->assertisObject($config->get('settings.Company'));
        $this->assertEquals($data['settings']['Company']['company_name'], $config->get('settings.Company.company_name'));
        $this->assertEquals($data['settings']['Image_verification']['use_for']['form_builder'], $config->get('settings.Image_verification.use_for.form_builder'));

        $this->assertNull($config->get('undefined'));
        $this->assertNull($config->get('undefined.undefined'));
        $this->assertNull($config->get('config.undefined'));
        $this->assertNull($config->get('config.dir.undefined'));
    }

    public function testDataExist()
    {
        $config = new DataLoader(['test' => 'foo']);

        $this->assertTrue($config->isReadOnly());
        $this->assertTrue($config->offsetExists('test'));
        $this->assertFalse($config->offsetExists('missing'));
        $this->assertTrue($config->offsetExists('test'));
    }

    public function testIfGetData()
    {
        $config = new DataLoader();
        $config->setWritable();

        $this->assertEquals('IfGet', $config->get('test1.foo', 'IfGet'));
        $this->assertEquals('IfGet', $config->get('test2.bar', 'IfGet'));

        $config->offsetSet('file', array('dir' => array('root' => __DIR__)));
        $this->assertEquals(__DIR__, $config->get('file.dir.root', '/'));
        $this->assertEquals('/', $config->get('file.dir.lib', '/'));

        $config->offsetUnset('file');

        $this->assertEquals('/', $config->get('file.dir.root', '/'));
    }

    public function testSetData()
    {
        $config = new DataLoader([
            'test' => [
                'hello' => [
                    'world1' => 'SetData',
                ],
            ],
        ]);
        $config->setWritable();
        $this->assertSame('SetData', $config->get('test.hello.world1'));

        $config->offsetSet('test', ['hello' => ['world2' => 'Test SetData']]);
        $this->assertEquals('Test SetData', $config->get('test.hello.world2'));

        $config->offsetSet('file', array('dir' => array('root' => __DIR__)));
        $this->assertEquals(__DIR__, $config->get('file.dir.root'));

        $config->offsetSet('config', array('debugger__token' => 'debug'));
        $this->assertEquals('debug', $config->get('config.debugger__token'));
    }

    public function testDeleteData()
    {
        $config = new DataLoader([
            'file' => [
                'dir' => [
                    'root' => __DIR__,
                ],
            ],
        ]);
        $config->setWritable();

        $config->offsetUnset('file');
        $this->assertNull($config->get('file.dir.root'));

        $config->offsetSet('config', array('dir' => array('root' => __DIR__)));
        $config->offsetUnset('config');

        $this->assertNull($config->offsetGet('config.dir.root'));
        $this->assertNull($config->offsetGet('config.dir'));
        $this->assertNull($config->get('config.dir.root'));
        $this->assertNull($config->get('config.dir'));
        $this->assertNull($config->get('config'));
    }

    public function testReadingData()
    {
        $config = new DataLoader([
            'hello' => [
                'world' => 'Test Push',
            ],
        ]);
        $config->setWritable();

        $this->assertTrue($config->offsetExists('hello.world'));
        $this->assertSame('Test Push', $config->hello->world);
        $this->assertEquals('Test Push', $config->offsetGet('hello.world'));

        $config->setReadOnly();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config is read only');
        $config->offsetSet('hello.world', 'Test Push');
    }

    public function testMergeData()
    {
        $config = new DataLoader([
            'this' => 'is',
            'nested' => [
                'values' => 'awesome',
            ],
            'set' => [1, 2, 3],
        ]);

        $config->merge(new DataLoader([
            'nested' => [
                'thing' => 'added',
            ],
            'set' => ['yeah'],
        ]));

        $expected = [
            'this' => 'is',
            'nested' => [
                'values' => 'awesome',
                'thing' => 'added',
            ],
            'set' => [1, 2, 3, 'yeah'],
        ];

        $this->assertEquals($expected, $config->toArray());

        $config = new DataLoader();
        $this->expectException(\TypeError::class);
        $config->merge(1);
    }
}
