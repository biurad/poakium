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

use Biurad\UI\Exceptions\ParserException;

class SelfCloseNode extends AbstractNode
{
    /** @var string @read-only */
    public $name;

    /** @var array<int,AttributeNode> */
    public $attributes;

    /** @var AbstractNode|null */
    public $next;

    /** @var ElementNode|null */
    public $parent;

    /**
     * @param array<int,AttributeNode> $attributes
     */
    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function __toString(): string
    {
        $attrHtml = '';

        foreach ($this->attributes as $attribute) {
            $attrHtml .= (string) $attribute;
        }

        return '<' . $this->name . $attrHtml . '>';
    }

    /**
     * Checks if attribute(s) exists.
     *
     * @param array<int,string> $attrNames
     *
     * @throws ParserException if a duplicated attribute if found
     */
    public function has(array $attrNames, AttributeNode &$attrNode = null): bool
    {
        $attrName = null;

        foreach ($this->attributes as $attribute) {
            if (\in_array($attribute->name, $attrNames, true)) {
                if (null !== $attrName) {
                    throw new ParserException(\sprintf('Found a duplicated attribute "%s", expected one.', $attrName));
                }

                $attrName = $attribute->name;

                if (\array_key_exists(1, \func_get_args())) {
                    $attrNode = $attribute;
                }
            }
        }

        return null !== $attrName;
    }

    /**
     * Checks if attribute(s) exists in parent's children nodes.
     *
     * @param array<int,string> $attrNames
     * @throws ParserException if a duplicated attribute if found
     */
    public function parentHas(array $attrNames, AbstractNode $tagNode = null): bool
    {
        if (null !== $tagParent = $this->parent) {
            foreach ($tagParent->children as $childNode) {
                if (!$childNode instanceof SelfCloseNode) {
                    continue;
                }

                if ($childNode->has($attrNames)) {
                    if (\array_key_exists(1, \func_get_args())) {
                        $tagNode = $childNode;
                    }

                    return true;
                }
            }
        }

        return false;
    }
}
