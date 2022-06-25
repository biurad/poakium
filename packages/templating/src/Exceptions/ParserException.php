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

namespace Biurad\UI\Exceptions;

class ParserException extends \ParseError
{
    public static function forTagWithParent(string $tagName, ?string $attrName = null, ?string $parent = null, bool $first = false, bool $void = false, bool $voidAttr = false): self
    {
        $errorMessage = 'Tag "%s" ' . (!empty($parent) ? ('must be inside a tag with attribute "%s"' . ($first ? ' as the first' : '')) : ('' === $parent ? $parent : 'is unexpected here'));
        $errorArgs = [$tagName];

        if (null !== $attrName) {
            $errorMessage = \substr_replace($errorMessage, ' with attribute "%s"' . ($voidAttr ? ' expected no value' . (!empty($parent) ? ', and ' : ' ') : ' '), 8, 1);
            $errorArgs[] = $attrName;
        }

        if ($void) {
            $errorMessage .= ' and must be self closed.';
        }

        if (null !== $parent) {
            $errorArgs[] = $parent;
        }

        return new self(\vsprintf($errorMessage, $errorArgs));
    }
}
