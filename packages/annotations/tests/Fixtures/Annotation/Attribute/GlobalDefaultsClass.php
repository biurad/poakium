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

namespace Biurad\Annotations\Tests\Fixtures\Annotation\Attribute;

use Biurad\Annotations\Tests\Fixtures\Sample;

#[Sample(name: 'attribute')]
#[Sample('attribute_added')]
class GlobalDefaultsClass
{
    #[Sample(name: 'constant')]
    public const CONSTANT = 23;

    #[Sample('property')]
    public $attribute;

    #[Sample(name: 'specific_name')]
    public function withName(
        #[Sample('method_property', priority: 4)]
        string $parameter
    ): void {
    }

    #[Sample('specific_none', priority: 14)]
    public function noName(): void
    {
    }
}
