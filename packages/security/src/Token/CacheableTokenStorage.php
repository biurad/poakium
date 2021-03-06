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

namespace Biurad\Security\Token;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * TokenStorage contains a TokenInterface.
 *
 * It gives access to the token representing the current user authentication.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CacheableTokenStorage implements TokenStorageInterface, ResetInterface
{
    private ?TokenInterface $token = null;
    private \Closure $storage;

    /**
     * @param SessionInterface|CacheItemPoolInterface $storage
     * @param int|\DateTime|null                      $expiry
     */
    public function __construct(object $storage, $expiry = 60 * 60 * 24 * 30)
    {
        $this->storage = function (string $key, TokenInterface $token = null) use ($storage, $expiry): ?TokenInterface {
            if (1 === \func_num_args()) {
                if ($storage instanceof CacheItemPoolInterface) {
                    return $storage->getItem($key)->get();
                }

                if (\is_array($token = $storage->get($key))) {
                    [$token, $expiry] = $token;

                    if (\time() > $expiry) {
                        $this->setToken();

                        return null; // token has expired
                    }
                }

                return $token;
            }

            if (null === $token) {
                if ($storage instanceof CacheItemPoolInterface) {
                    $storage->deleteItem($key);
                } else {
                    $storage->remove($key);
                }
            } elseif ($storage instanceof SessionInterface) {
                if ($expiry instanceof \DateTimeInterface) {
                    $expiry = $expiry->getTimestamp();
                }
                $storage->set($key, $expiry ? [$token, $expiry] : $token);
            } else {
                $item = $storage->getItem($key)->set($token);
                $storage->save(\is_int($expiry) ? $item->expiresAfter(new \DateInterval('PT'.$expiry.'S')) : $item->expiresAt($expiry));
            }

            return null;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?TokenInterface
    {
        return $this->token ??= ($this->storage)(__CLASS__);
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(TokenInterface $token = null): void
    {
        ($this->storage)(__CLASS__, $this->token = $token);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->setToken();
    }
}
