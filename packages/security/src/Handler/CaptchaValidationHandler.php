<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security\Handler;

use Biurad\Security\Helper;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Request Captcha validation.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CaptchaValidationHandler
{
    public const RECAPTCHA = 'recaptcha';
    public const HCAPTCHA = 'hcaptcha';

    private array $validators = [
        self::RECAPTCHA => [null, ['g-recaptcha-response'], null],
        self::HCAPTCHA => [null, ['h-captcha-response'], null],
    ];

    /**
     * Add/Replace captcha validation.
     *
     * @param string            $type          The name of the captcha to use
     * @param string            $secret        The secret code to access
     * @param array<int,string> $parameterKeys The required parameters list from request body
     * @param callable|null     $handler       The handler is in form of: $handler($request, $credentials, $secret)
     */
    public function add(string $type, string $secret, array $parameterKeys = [], callable $handler = null): void
    {
        if ($validator = &$this->validators[$type] ?? null) {
            $secret = $validator[$secret] ?? $secret;
            $parameterKeys = \array_merge($validator[1] ?? [], $parameterKeys);
        }

        $validator = [$secret, $parameterKeys, $handler];
    }

    /**
     * Authenticate a request using captcha.
     */
    public function authenticate(ServerRequestInterface $request, string $type = self::RECAPTCHA): bool
    {
        [$secret, $parameters, $handler] = $this->validators[$type] ?? [null, [], null];
        $credentials = Helper::getParameterValues($request, $parameters);

        if (!isset($secret)) {
            throw new BadCredentialsException('Cannot authenticate request without secret key');
        }

        if (null !== $handler) {
            return $handler($request, $credentials, $secret);
        }

        if (self::RECAPTCHA === $type) {
            if (empty($captcha = $credentials['g-recaptcha-response'])) {
                return false;
            };

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . \urlencode($secret) . '&response=' . \urlencode($captcha);
            $response = \file_get_contents($url);
            $responseKeys = \json_decode($response, true);

            return $responseKeys['success'] ?? false;
        }

        if (self::HCAPTCHA === $type) {
            if (empty($captcha = $credentials['h-captcha-response'])) {
                return false;
            };

            $url = 'https://hcaptcha.com/siteverify?secret=' . \urlencode($secret) . '&response=' . \urlencode($captcha) . '&remoteip=';
            $response = \file_get_contents($url . ($request->getServerParams()['REMOTE_ADDR'] ?? ''));
            $responseKeys = \json_decode($response, true);

            return $responseKeys['success'] ?? false;
        }

        return false;
    }
}
