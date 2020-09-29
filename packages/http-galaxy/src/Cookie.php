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

namespace Biurad\Http;

use Biurad\Http\Interfaces\CookieInterface;
use Biurad\Http\Utils\CookieUtil;
use GuzzleHttp\Cookie\SetCookie;
use InvalidArgumentException;

/**
 * Represent singular cookie header value with packing abilities.
 *
 * @see http://tools.ietf.org/search/rfc6265
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class Cookie extends SetCookie implements CookieInterface
{
    public const SAMESITE_COLLECTION = ['lax', 'strict', 'none', null];

    /**
     * @var array
     */
    private static $defaults = [
        'Name'     => null,
        'Value'    => null,
        'Domain'   => null,
        'Path'     => '/',
        'Max-Age'  => null,
        'Expires'  => null,
        'Secure'   => false,
        'Discard'  => false,
        'HttpOnly' => false,
        'SameSite' => null,
    ];

    /**
     * @var array Cookie data
     */
    private $data;

    /**
     * @param array $data Array of cookie data provided by a Cookie parser
     */
    public function __construct(array $data = [])
    {
        /** @var null|array $replaced will be null in case of replace error */
        $replaced = \array_replace(self::$defaults, $data);

        if ($replaced === null) {
            throw new InvalidArgumentException('Unable to replace the default values for the Cookie.');
        }

        if (!\in_array($replaced['SameSite'], self::SAMESITE_COLLECTION, true)) {
            throw new InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }
        $this->setSameSite($replaced['SameSite']);

        parent::__construct($this->data = $replaced);
    }

    /**
     * {@inheritdoc}
     */
    public function setExpires($timestamp): void
    {
        parent::setExpires(CookieUtil::normalizeExpires($timestamp));
    }

    /**
     * {@inheritdoc}
     */
    public function setSameSite($sameSite): void
    {
        $this->data['SameSite'] = $sameSite;
    }

    /**
     * {@inheritdoc}
     */
    public function getSameSite(): ?string
    {
        return $this->data['SameSite'];
    }

    /**
     * {@inheritdoc}
     */
    public function matches(CookieInterface $cookie): bool
    {
        return $this->name === $cookie->name && $this->domain === $cookie->domain && $this->path === $cookie->path;
    }

    /**
     * Evaluate if this cookie should be persisted to storage
     * that survives between requests.
     *
     * @param bool $allowSessionCookies If we should persist session cookies
     *
     * @return bool
     */
    public function shouldPersist($allowSessionCookies = false)
    {
        if ($this->isExpired() || $allowSessionCookies) {
            return true;
        }

        return false;
    }

    /**
     * Proxy for the native setcookie function - to allow mocking in unit tests so that they do not fail when headers
     * have been sent. But can serve better when used natively to set cookies.
     *
     * @param string      $name
     * @param string      $value
     * @param int         $expire
     * @param string      $path
     * @param string      $domain
     * @param bool        $secure
     * @param bool        $httponly
     * @param null|string $sameSite
     *
     * @return bool
     *
     * @see setcookie
     */
    public static function setcookie(
        $name,
        $value = '',
        $expires = 0,
        $path = '',
        $domain = '',
        $secure = false,
        $httponly = false,
        $samesite = null
    ) {
        if (\PHP_VERSION_ID >= 70300) {
            return \setcookie($name, $value, \compact('path', 'expires', 'domain', 'secure', 'httponly', 'samesite'));
        }

        return \setcookie(
            $name,
            $value,
            $expires,
            $path . ($samesite ? "; SameSite=$samesite" : ''),
            $domain,
            $secure,
            $httponly
        );
    }
}
