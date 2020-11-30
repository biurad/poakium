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

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 */
class Response implements ResponseInterface, StatusCodeInterface
{
    use Traits\ResponseDecoratorTrait {
        getResponse as private;
    }

    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param null|resource|StreamInterface|string $body    Response body
     * @param string                               $version Protocol version
     * @param null|string                          $reason  Reason phrase (optional)
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        $this->message = new Psr7Response($status, $headers, $body, $version, $reason);
    }
}
