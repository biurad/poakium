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

namespace Biurad\Monorepo;

use Symfony\Component\Console\Application;

(static function (): void {
    if (\file_exists($autoload = __DIR__.'/../vendor/autoload.php')) {
        require_once $autoload;
    } elseif (\file_exists($autoload = __DIR__.'/../../../vendor/autoload.php')) {
        require_once $autoload;
    }

    $application = new Application('Poakium Monorepo', 'dev');
    $application->add(new WorkflowCommand($_SERVER['PWD'] ?? \getcwd()));
    $application->setDefaultCommand('workflow', true)->run();
})();
