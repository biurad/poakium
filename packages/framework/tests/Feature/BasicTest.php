<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Feature;

use App\Tests\TestCase;
use App\Tests\Traits\InteractsWithHttp;
use Flight\Routing\Route;
use Psr\Http\Message\ResponseInterface;

class BasicTest extends TestCase
{
    use InteractsWithHttp;

    /**
     * PHPUnit's data providers allow to execute the same tests repeated times
     * using a different set of data each time.
     *
     * @dataProvider getPublicUrls
     * @runInSeparateProcess
     */
    public function testRoutingActionWorks(string $uri, string $body): void
    {
        $this->makeApp()->match($uri, Route::DEFAULT_METHODS, fn () => $body)->bind('test');

        $response = $this->makeApp()->handle($this->request($uri));
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEquals($body, (string) $response->getBody());
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            \sprintf('The %s public URL loads correctly.', $uri)
        );
    }

    public function getPublicUrls(): \Generator
    {
        yield ['/site/hello', 'I ♥ Biurad PHP Framework'];

        yield ['/site/blog/', 'Welcome To A Blog HomePage'];

        yield ['/site/login', 'Welcome To Login Page'];
    }
}
