<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BasicAuthMiddleware implements MiddlewareInterface
{

	/** @var string */
	private $title;

	/** @var mixed[] */
	protected $users = [];

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
			'password' => $password,
			'unsecured' => $unsecured,
		];
		return $this;
	}

	protected function auth(string $user, string $password): bool
	{
		if (!isset($this->users[$user]))
			return false;

		if (
			($this->users[$user]['unsecured'] === true && !hash_equals($password, $this->users[$user]['password'])) ||
			($this->users[$user]['unsecured'] === false && !password_verify($password, $this->users[$user]['password']))
		) {
			return false;
		}

		return true;
	}

	/**
	 * @return mixed[]|null
	 */
	protected function parseAuthorizationHeader(string $header): ?array
	{
		if (strpos($header, 'Basic') !== 0) {
			return null;
        }

		$header = explode(':', (string) base64_decode(substr($header, 6), true), 2);
		return [
			'username' => $header[0],
			'password' => $header[1] ?? null,
		];
	}

}
