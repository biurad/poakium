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

namespace Biurad\Http\Traits;

use Psr\Http\Message\UriInterface;

trait UriDecorationTrait
{
    /** @var UriInterface */
    private $uri;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->uri->__toString();
    }

    /**
     * Exchanges the underlying uri with another.
     */
    public function withUri(UriInterface $uri): UriInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->uri->getScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        return $this->uri->getAuthority();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return $this->uri->getUserInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->uri->getHost();
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->uri->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->uri->getFragment();
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withScheme($scheme);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withUserInfo($user, $password);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withHost($host);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withPort($port);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($path);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withQuery($query);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment): UriInterface
    {
        $new = clone $this;
        $new->uri = $this->uri->withFragment($fragment);

        return $new;
    }
}
