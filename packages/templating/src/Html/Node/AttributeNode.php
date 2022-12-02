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

namespace Biurad\UI\Html\Node;

class AttributeNode extends AbstractNode
{
    /** @var string @read-only */
    public $name, $space, $quote;

    /** @var string|null */
    public $value;

    /** @var SelfCloseNode|ElementNode|null */
    public $node;

    public function __construct(string $name, ?string $value = null, string $space = ' ', bool $noQuote = false)
    {
        $this->name = $name;
        $this->value = $value;
        $this->space = $space;
        $this->quote = $noQuote ? '' : (null !== $value && \str_contains($value, '"') ? '\'' : '"');
    }

    public function __toString(): string
    {
        if (null === $value = $this->value) {
            return $this->space . $this->name;
        }

        return $this->space . $this->name . '=' . $this->quote . $value . $this->quote;
    }
}
