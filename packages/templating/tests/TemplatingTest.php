<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\UI\Tests;

use Biurad\UI\Exceptions\LoaderException;
use Biurad\UI\Exceptions\RenderException;
use Biurad\UI\Helper\SlotsHelper;
use Biurad\UI\Html\HtmlElement;
use Biurad\UI\Interfaces\HtmlInterface;
use Biurad\UI\Interfaces\TemplateInterface;
use Biurad\UI\Renders\LatteRender;
use Biurad\UI\Renders\PhpNativeRender;
use Biurad\UI\Renders\TwigRender;
use Biurad\UI\Storage\ArrayStorage;
use Biurad\UI\Storage\ChainStorage;
use Biurad\UI\Storage\FilesystemStorage;
use Biurad\UI\Template;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TemplatingTest extends TestCase
{
    public function testConstructor(): void
    {
        $template = new Template(new ArrayStorage(['hello.php' => 'Hello World']));

        $this->assertInstanceOf(TemplateInterface::class, $template);
        $this->assertNull($template->find('hello.php'));
        $this->assertEmpty($template->getRenders());

        $this->expectExceptionMessage('Unable to load template for "hello.php", file does not exist.');
        $this->expectException(LoaderException::class);

        $template->render('hello.php');
    }

    /**
     * @dataProvider dataTemplateArray
     */
    public function testTemplating(string $templateFile): void
    {
        $dir = __DIR__ . '/Fixtures';
        $fileStorage = new FilesystemStorage([$dir . '/templates']);
        $arrayStorage = new ArrayStorage(['hello_array.latte' => '{template(\'hello_twig\', [\'firstname\' => $firstname])|noEscape}']);
        $storage = new ChainStorage([$fileStorage, $arrayStorage]);

        $template = new Template($storage, __DIR__ . '/caches');
        $template->addNamespace('Extended', $dir . '/Bundles');
        $template->addRender(new PhpNativeRender(), new TwigRender(), new LatteRender());

        $template->getRender('phtml')->setHelpers([new SlotsHelper()]);

        $this->assertStringEqualsFile(
            $dir . '/template1.txt',
            $template->render($templateFile, ['firstname' => 'Divine']) . $template->render('hello_array', ['firstname' => 'Sparkle'])
        );
    }

    public function testSetGlobalMethodWithLogger(): void
    {
        $fileStorage = new FilesystemStorage([__DIR__ . '/Fixtures/templates'], new NullLogger());
        $template = new Template($fileStorage);
        $phpRender = new PhpNativeRender(['php', 'phtml'], [new SlotsHelper()]);

        $template->addGlobal('firstname', 'Divine');
        $template->addNamespace('Extended', __DIR__ . '/Fixtures/Bundles');
        $template->addRender($phpRender, new TwigRender(), new LatteRender());
        $this->assertEquals(['firstname' => 'Divine'], $template->getGlobal());

        $this->assertNull($template->renderTemplates(['hello_array'], []));
        $this->assertStringEqualsFile(__DIR__ . '/Fixtures/template2.txt', $template->renderTemplates(['hello_twig'], []));
    }

    public function testGetRenderError(): void
    {
        $fileStorage = new FilesystemStorage([__DIR__ . '/Fixtures/templates']);
        $template = new Template($fileStorage);

        $this->expectExceptionMessage('Could not find a render for file extension "php".');
        $this->expectException(LoaderException::class);

        $template->getRender('php');
    }

    public function testArrayStorage(): void
    {
        $arrayStorage = new ArrayStorage([
            'hello.view' => new Fixtures\ViewString(),
            'hello_page' => new \stdClass(),
            'namespaced.view' => '<?php $this->extend("@Extended::base.layout_native"); ?>',
        ]);
        $template = new Template($arrayStorage);
        $template->addRender(new PhpNativeRender(['view'], [new SlotsHelper()]));

        $this->assertEquals('Hello, Divine!', $template->render('hello', ['firstname' => 'Divine']));

        try {
            $arrayStorage->addLocation('hi_page');
        } catch (LoaderException $e) {
            $this->assertEquals('Cannot use [hi_page] for templates loading.', $e->getMessage());
        }

        try {
            $arrayStorage->load('hello_page');
        } catch (LoaderException $e) {
            $this->assertEquals('Failed to load "hello_page" as it\'s source isn\'t stringable.', $e->getMessage());
        }

        try {
            $template->render('namespaced');
        } catch (LoaderException $e) {
            $this->assertEquals('No hint path(s) defined for [Extended] namespace.', $e->getMessage());
        }
    }

    public function testFormattedHtmlTemplateWithLevel4Indent(): void
    {
        $content = \file_get_contents(($dir = __DIR__ . '/Fixtures') . '/template3.txt');
        $nodes = HtmlElement::generateNodes($content);

        $this->assertCount(2, $nodes);
        $this->assertStringEqualsFile($dir . '/template4.txt', HtmlElement::generateHtml($nodes, null, ['indentLevel' => 4]) . "\n");

        try {
            HtmlElement::generateNodes($dir);
        } catch (RenderException $e) {
            $this->assertEquals('Unable to render provided html into element nodes.', $e->getMessage());
        }
    }

    public function dataTemplateArray(): array
    {
        return [
            ['hello_twig'],
            [__DIR__ . '/Fixtures/templates/hello_twig'],
            [__DIR__ . '/Fixtures/templates/hello_twig.twig'],
        ];
    }
}
