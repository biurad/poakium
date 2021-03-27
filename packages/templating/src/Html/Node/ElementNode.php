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

class ElementNode extends SelfCloseNode
{
    /** @var string */
    public $html;

    /** @var array<int,ElementNode> */
    public $children = [];

    /**
     * @param array<int,AttributeNode> $attributes
     */
    public function __construct(string $name, string $html = '', array $attributes = [])
    {
        parent::__construct($name, $attributes);
        $this->html = $html;
    }

    public function __toString(): string
    {
        $attrHtml = '';

        foreach ($this->attributes as $attribute) {
            $attrHtml .= (string) $attribute;
        }

        return '<' . $this->name . $attrHtml . '>' . $this->html . '</' . $this->name . '>';
    }
}
