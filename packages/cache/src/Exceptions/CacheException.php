<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache\Exceptions;

use Psr\Cache\CacheException as Psr6Exception;
use Psr\SimpleCache\CacheException as Psr16Exception;

class CacheException extends \RuntimeException implements Psr6Exception, Psr16Exception
{
}
