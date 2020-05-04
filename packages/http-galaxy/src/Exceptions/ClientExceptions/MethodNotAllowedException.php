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

namespace BiuradPHP\Http\Exceptions\ClientExceptions;

use BiuradPHP\Http\Exceptions\ClientException;

/**
 * HTTP 405 exception.
 */
class MethodNotAllowedException extends ClientException
{
    /**
     * @var int
     */
    protected $code = 405;

    public function __construct()
    {
        parent::__construct($this->code, $this->getPrevious());
    }
}
