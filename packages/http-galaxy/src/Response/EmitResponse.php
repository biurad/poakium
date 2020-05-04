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

use Psr\Http\Message\ResponseInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

/**
 * Emits a response for a PHP SAPI environment.
 *
 * Emits the status line and headers via the header() function, and the
 * body content via the output buffer.
 */
class EmitResponse extends SapiStreamEmitter
{
    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response): bool
    {
        $emitResponse = parent::emit($response);

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $this->closeOutputBuffers(0, true);
        }

        return $emitResponse;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @final
     */
    private function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = \count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    /**
     * Asserts response body is empty or status code is 204, 205 or 304
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public static function isResponseEmpty(ResponseInterface $response): bool
    {
        $contents = (string) $response->getBody();
        return empty($contents) || in_array($response->getStatusCode(), [204, 205, 304], true);
    }
}
