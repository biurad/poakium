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

namespace App\Tests\Traits;

trait InteractsWithFaker
{
    protected ?\Faker\Generator $faker;

    /**
     * Create a Faker instance for the given locale.
     */
    protected function makeFaker(string $locale = null): \Faker\Generator
    {
        return $this->faker ??= \Faker\Factory::create($locale ?? \Faker\Factory::DEFAULT_LOCALE);
    }
}
