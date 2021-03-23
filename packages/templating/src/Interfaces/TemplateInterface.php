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

use Biurad\UI\Exceptions\LoaderException;

/**
 * An interface for the template engine.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface TemplateInterface
{
    /**
     * Namespace/viewName separator.
     */
    public const NS_SEPARATOR = '::';

    /**
     * Add a namespace hint to the finder.
     *
     * @param string|string[] $hints list of directories to look into
     */
    public function addNamespace(string $namespace, $hints): void;

    /**
     * Registers a Global.
     *
     * New globals can be added before compiling or rendering a template;
     * but after, you can only update existing globals.
     *
     * @param string $name  The global name
     * @param mixed  $value The global value
     */
    public function addGlobal(string $name, $value): void;

    /**
     * Attach the view render(s).
     */
    public function addRender(RenderInterface ...$renders): void;

    /**
     * Renders a template.
     *
     * @param string              $template   A template name or a namespace name to path
     * @param array<string,mixed> $parameters An array of parameters to pass to the template
     *
     * @throws LoaderException if the template cannot be rendered
     *
     * @return string The evaluated template as a string
     */
    public function render(string $template, array $parameters = []): string;

    /**
     * Get source for given template. Path might include namespace prefix or extension.
     *
     * @param string $template A template name or a namespace name to path
     *
     * @throws LoaderException if unable to load template from namespace
     *
     * @return string|null Expects the template absolute file or null
     */
    public function find(string $template): ?string;
}
