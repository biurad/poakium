<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Scaffold\Exceptions;

use RuntimeException;
use Symfony\Component\Console\Exception\ExceptionInterface;

/**
 * An exception whose output is displayed as a clean error.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class RuntimeCommandException extends RuntimeException implements ExceptionInterface
{
}
