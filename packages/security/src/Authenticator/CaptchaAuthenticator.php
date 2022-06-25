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

namespace Biurad\Security\Authenticator;

use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * ReCaptcha/HCaptcha validation authenticator.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CaptchaAuthenticator implements AuthenticatorInterface
{
    private ?string $reCaptchaSecret;
    private ?string $hCaptchaSecret;

    public function __construct(string $reCaptchaSecret = null, string $hCaptchaSecret = null)
    {
        if (!$reCaptchaSecret || !$hCaptchaSecret) {
            throw new \InvalidArgumentException('You must provide a reCaptcha secret and/or a hCaptcha secret.');
        }

        $this->reCaptchaSecret = $reCaptchaSecret;
        $this->hCaptchaSecret = $hCaptchaSecret;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return 'POST' === $request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials, string $firewallName): ?TokenInterface
    {
        if (isset($credentials['g-recaptcha-response'])) {
            if (empty($captcha = $credentials['g-recaptcha-response'])) {
                throw new BadCredentialsException('The presented captcha cannot be empty.');
            }

            if (empty($secret = $this->reCaptchaSecret)) {
                throw new BadCredentialsException('You must provide a reCaptcha secret.');
            }

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . \urlencode($secret) . '&response=' . \urlencode($captcha);
            $response = \json_decode(\file_get_contents($url), true);
        } elseif (isset($credentials['h-captcha-response'])) {
            if (empty($captcha = $credentials['h-captcha-response'])) {
                throw new BadCredentialsException('The presented captcha cannot be empty.');
            }

            if (empty($secret = $this->hCaptchaSecret)) {
                throw new BadCredentialsException('You must provide a hCaptcha secret.');
            }

            $url = 'https://hcaptcha.com/siteverify?secret=' . \urlencode($secret) . '&response=' . \urlencode($captcha) . '&remoteip=';
            $response = \json_decode(\file_get_contents($url . ($request->getServerParams()['REMOTE_ADDR'] ?? '')), true);
        }

        if (isset($response) && $response['success'] ?? false) {
            throw new BadCredentialsException('The presented captcha is invalid.');
        }

        return null;
    }
}
