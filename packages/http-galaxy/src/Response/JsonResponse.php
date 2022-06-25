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

namespace Biurad\Http\Response;

use Biurad\Http\Response;
use Biurad\Http\Exception;
use Symfony\Component\HttpFoundation\JsonResponse as HttpFoundationJsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * JSON response with Content-Type header to application/json.
 */
class JsonResponse extends Response
{
    /**
     * Create a JSON response with the given data.
     *
     * @param iterable|array|\JsonSerializable $data            data to convert to JSON
     * @param int                              $status          integer status code for the response; 200 by default
     * @param array                            $headers         array of headers to use at initialization
     * @param int                              $encodingOptions JSON encoding options to use
     * @param string|null                      $callback The JSONP callback or null to use none
     *
     * @throws Exception\InvalidArgumentException if unable to encode the $data to JSON
     */
    public function __construct($data, int $status = 200, array $headers = [], callable $callback = null)
    {
        $this->message = new HttpFoundationJsonResponse($data, $status, $headers);

        if (null !== $callback) {
            $this->message->setCallback($callback);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return HttpFoundationJsonResponse
     */
    public function getResponse(): HttpFoundationResponse
    {
        return parent::getResponse();
    }

    /**
     * @param iterable|array|\JsonSerializable $data
     */
    public function withPayload($data): self
    {
        $new = clone $this;
        $new->message->setData($data);

        return $new;
    }

    public function withEncodingOptions(int $encodingOptions): self
    {
        $new = clone $this;
        $new->message->setEncodingOptions($encodingOptions);

        return $new;
    }
}
