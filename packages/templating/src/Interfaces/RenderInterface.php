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

interface RenderInterface
{
    /**
     * Configure view engine with new loader.
     *
     * @param LoaderInterface $loader
     *
     * @return RenderInterface
     */
    public function withLoader(LoaderInterface $loader): RenderInterface;

    /**
     * Get currently associated engine loader.
     *
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface;

    /**
     * Get the template type o content associated with a Render.
     * This must attempt to use existed cache if such presented
     *
     * @param string              $template   A template name or a namepace name to path
     * @param array<string,mixed> $parameters An array of parameters to pass to the template
     *
     * @return string of rendered template
     */
    public function render(string $template, array $parameters): string;
}
