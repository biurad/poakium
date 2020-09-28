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

namespace Biurad\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BasicAuthMiddleware implements MiddlewareInterface
{
    /** @var mixed[] */
    protected $users = [];

    /** @var string */
    private $title;

    public function __construct(string $title = 'Restrict zone')
    {
        $this->title = $title;
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $this->parseAuthorizationHeader($request->getHeaderLine('Authorization'));

        if ($authorization !== null && $this->auth($authorization['username'], $authorization['password'])) {
            return $handler->handle($request->withAttribute('username', $authorization['username']));
        }

        return $handler
            ->handle($request)
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="' . $this->title . '"');
    }

    public function addUser(string $user, string $password, bool $unsecured = false): self
    {
        $this->users[$user] = [
            'password'  => $password,
            'unsecured' => $unsecured,
        ];

        return $this;
    }

    protected function auth(string $user, string $password): bool
    {
        if (!isset($this->users[$user])) {
            return false;
        }

        if (
            ($this->users[$user]['unsecured'] && !\hash_equals($password, $this->users[$user]['password'])) ||
            (!$this->users[$user]['unsecured'] && !\password_verify($password, $this->users[$user]['password']))
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return null|mixed[]
     */
    protected function parseAuthorizationHeader(string $header): ?array
    {
        if (\strpos($header, 'Basic') !== 0) {
            return null;
        }

        $header = \explode(':', (string) \base64_decode(\substr($header, 6), true), 2);

        return [
            'username' => $header[0],
            'password' => $header[1] ?? null,
        ];
    }
}
