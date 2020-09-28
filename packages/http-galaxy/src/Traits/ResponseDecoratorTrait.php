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

use Psr\Http\Message\ResponseInterface;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait ResponseDecoratorTrait
{
    use MessageDecoratorTrait {
        getMessage as private;
    }

    /**
     * Returns the decorated response.
     *
     * Since the underlying Response is immutable as well
     * exposing it is not an issue, because it's state cannot be altered
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        /** @var ResponseInterface $message */
        $message = $this->getMessage();

        return $message;
    }

    /**
     * Exchanges the underlying response with another.
     *
     * @param ResponseInterface $response
     *
     * @return self
     */
    public function withResponse(ResponseInterface $response): self
    {
        $new          = clone $this;
        $new->message = $response;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new          = clone $this;
        $new->message = $this->getResponse()->withStatus($code, $reasonPhrase);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->getResponse()->getReasonPhrase();
    }
}
