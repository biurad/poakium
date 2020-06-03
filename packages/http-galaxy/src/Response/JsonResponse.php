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

namespace BiuradPHP\Http\Response;

use BiuradPHP\Http\Response;
use GuzzleHttp\Exception;
use Psr\Http\Message\StreamInterface;
use BiuradPHP\Http\Traits\InjectContentTypeTrait;

use function GuzzleHttp\Psr7\stream_for;

/**
 * JSON response.
 *
 * Allows creating a response by passing data to the constructor; by default,
 * serializes the data to JSON, sets a status code of 200 and sets the
 * Content-Type header to application/json.
 */
class JsonResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Default flags for json_encode; value of:
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @const int
     */
    const DEFAULT_JSON_FLAGS = 79;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @var int
     */
    private $encodingOptions;

    /**
     * Create a JSON response with the given data.
     *
     * Default JSON encoding is performed with the following options, which
     * produces RFC4627-compliant JSON, capable of embedding into HTML.
     *
     * - JSON_HEX_TAG
     * - JSON_HEX_APOS
     * - JSON_HEX_AMP
     * - JSON_HEX_QUOT
     * - JSON_UNESCAPED_SLASHES
     *
     * @param mixed $data Data to convert to JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int $encodingOptions JSON encoding options to use.
     * @throws Exception\InvalidArgumentException if unable to encode the $data to JSON.
     */
    public function __construct(
        $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $this->setPayload($data);
        $this->encodingOptions = $encodingOptions;

        $headers = $this->injectContentType('application/json', $headers);

        parent::__construct($status, $headers, $this->jsonEncode($data, $this->encodingOptions));
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $data
     */
    public function withPayload($data) : JsonResponse
    {
        $new = clone $this;
        $new->setPayload($data);

        return $this->updateBodyFor($new);
    }

    public function getEncodingOptions() : int
    {
        return $this->encodingOptions;
    }

    public function withEncodingOptions(int $encodingOptions) : JsonResponse
    {
        $new = clone $this;
        $new->encodingOptions = $encodingOptions;

        return $this->updateBodyFor($new);
    }

    private function createBodyFromJson(string $json) : StreamInterface
    {
        $body = stream_for('php://temp', ['mode' => 'wb+']);
        $body->write($json);
        $body->rewind();

        return $body;
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param mixed $data
     * @throws Exception\InvalidArgumentException if unable to encode the $data to JSON.
     */
    private function jsonEncode($data, int $encodingOptions) : string
    {
        if (is_resource($data)) {
            throw new Exception\InvalidArgumentException('Cannot JSON encode resources');
        }

        // Clear json_last_error()
        json_encode(null);

        $json = json_encode($data, $encodingOptions);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                json_last_error_msg()
            ));
        }

        return $json;
    }

    /**
     * @param mixed $data
     */
    private function setPayload($data) : void
    {
        if (is_object($data)) {
            $data = clone $data;
        }

        $this->payload = $data;
    }

    /**
     * Update the response body for the given instance.
     *
     * @param self $toUpdate Instance to update.
     * @return JsonResponse Returns a new instance with an updated body.
     */
    private function updateBodyFor(JsonResponse $toUpdate) : JsonResponse
    {
        $json = $this->jsonEncode($toUpdate->payload, $toUpdate->encodingOptions);
        $body = $this->createBodyFromJson($json);

        return $toUpdate->withBody($body);
    }
}
