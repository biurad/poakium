<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 /*
 *--------------------------------------------------------------------------
 * Cli & CGI WebServer Booting
 *-------------------------------------------------------------------------
 *
 * Decline static file requests back to the PHP built-in web-server
 *
 */
if (\in_array(\PHP_SAPI, ['cli-server', 'cgi-fcgi'], true)) {
    $path = \realpath(__DIR__ . \parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH));

    if (__FILE__ !== $path && \is_file((string) $path)) {
        return false;
    }
    unset($path);
}

require __DIR__ . '/public/index.php';
