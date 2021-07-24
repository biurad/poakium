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
use Biurad\Http\Traits\InjectContentTypeTrait;
use Biurad\Http\Exception;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * JSON response with Content-Type header to application/json.
 */
class JsonResponse extends Response
{
    use InjectContentTypeTrait;

    /** @var int produces RFC4627-compliant JSON */
    public const DEFAULT_JSON_FLAGS = \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR;

    /** @var mixed */
    private $payload;

    /** @var int */
    private $encodingOptions;

    /**
     * Create a JSON response with the given data.
     *
     * @param iterable|array|\JsonSerializable $data            data to convert to JSON
     * @param int                              $status          integer status code for the response; 200 by default
     * @param array                            $headers         array of headers to use at initialization
     * @param int                              $encodingOptions JSON encoding options to use
     *
     * @throws Exception\InvalidArgumentException if unable to encode the $data to JSON
     */
    public function __construct(
        $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $this->payload = \is_object($data) ? clone $data : $data;
        $this->encodingOptions = $encodingOptions;
        $headers = $this->injectContentType('application/json', $headers);

        parent::__construct($status, $headers, $this->jsonEncode($data, $this->encodingOptions));
    }

    /**
     * @return iterable|array|\JsonSerializable
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param iterable|array|\JsonSerializable $data
     */
    public function withPayload($data): JsonResponse
    {
        $new = clone $this;
        $new->payload = \is_object($data) ? clone $data : $data;

        return $this->updateBodyFor($new);
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    public function withEncodingOptions(int $encodingOptions): JsonResponse
    {
        $new = clone $this;
        $new->encodingOptions = $encodingOptions;

        return $this->updateBodyFor($new);
    }

    private function createBodyFromJson(string $json): StreamInterface
    {
        $body = new Stream('php://temp', ['mode' => 'wb+']);
        $body->write($json);
        $body->rewind();

        return $body;
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param iterable|array|\JsonSerializable $data
     *
     * @throws Exception\InvalidArgumentException if unable to encode the $data to JSON
     */
    private function jsonEncode($data, int $encodingOptions): string
    {
        if (\is_resource($data)) {
            throw new Exception\InvalidArgumentException('Cannot JSON encode resources');
        }

        return \json_encode($data, $encodingOptions);
    }

    /**
     * Update the response body for the given instance.
     *
     * @param self $toUpdate instance to update
     *
     * @return JsonResponse returns a new instance with an updated body
     */
    private function updateBodyFor(JsonResponse $toUpdate): JsonResponse
    {
        $json = $this->jsonEncode($toUpdate->payload, $toUpdate->encodingOptions);
        $body = $this->createBodyFromJson($json);

        return $toUpdate->withBody($body);
    }
}
