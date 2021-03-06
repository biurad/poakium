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

namespace Biurad\UI\Interfaces;

/**
 * A fluent interface for building renders.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface RenderInterface
{
    /**
     * Includes the template resolver into render.
     */
    public function withLoader(TemplateInterface $loader): RenderInterface;

    /**
     * Set the render's file extension(s).
     */
    public function withExtensions(string ...$extensions): void;

    /**
     * Gets the file extensions associated render.
     *
     * @return array<int,string>
     */
    public function getExtensions(): array;

    /**
     * Get the template type o content associated with a Render.
     *
     * @param string              $template   A template name or a namespace name to path
     * @param array<string,mixed> $parameters An array of parameters to pass to the template
     *
     * @return string of rendered template
     */
    public function render(string $template, array $parameters): string;
}
