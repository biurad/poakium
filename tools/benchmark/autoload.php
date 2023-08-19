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

 /** @var \Composer\Autoload\ClassLoader */
$composer = require __DIR__.'/../../vendor/autoload.php';

$composer->addPsr4("Biurad\\Annotations\\Tests\\Fixtures\\", [
    __DIR__."/../../packages/annotations/tests/Fixtures/",
    __DIR__."/../../../packages/annotations/tests/Fixtures/",
]);

$composer->register();
