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
        if (null === $replaced = \array_replace(self::$defaults, $data)) {
            throw new InvalidArgumentException('Unable to replace the default values for the Cookie.');
        }

        // Set HttpOnly to opposite if Secure exists
        if (isset($replaced['Secure'])) {
            $replaced['HttpOnly'] = !$replaced['Secure'];
        }

        if (!\in_array($replaced['SameSite'], self::SAMESITE_COLLECTION, true)) {
            throw new InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }
        $this->setSameSite($replaced['SameSite']);

        parent::__construct($this->data = $replaced);
    }

    /**
     * Create a new cookie object from a string.
     *
     * @param string $cookie Set-Cookie header string
     */
    public static function fromString(string $cookie): self
    {
        return new self(parent::fromString($cookie)->toArray());
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
        return $this->getName() === $cookie->getName() &&
            $this->getDomain() === $cookie->getDomain() &&
            $this->getPath() === $cookie->getPath();
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
}
