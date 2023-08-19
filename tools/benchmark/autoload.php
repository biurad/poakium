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

require __DIR__.'/../../vendor/autoload.php';

\spl_autoload_register(function (string $class): void {
    $names = \explode('\\', $class);

    if (['Biurad', 'Tests', 'Fixtures'] === [$names[0] ?? null, $names[2] ?? null, $names[3] ?? null]) {
        $className = \implode('\\', \array_slice($names, 4));

        if ('Annotations' === $names[1]) {
            if ('annotated_function' === \end($names)) {
                $className = 'Annotation/function';
            }

            $files = [
                __DIR__.'/../../packages/annotations/tests/Fixtures/'.$className.'.php',
                __DIR__.'/../../../packages/annotations/tests/Fixtures/'.$className.'.php',
            ];

            foreach ($files as $file) {
                if (\file_exists($file)) {
                    require $file;
                }
            }
        }
    }
});
