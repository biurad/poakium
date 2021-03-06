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

namespace Biurad\UI\Renders;

use Biurad\UI\Interfaces\RenderInterface;
use Biurad\UI\Interfaces\TemplateInterface;

/**
 * Render engine with ability to switch environment and loader.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class AbstractRender implements RenderInterface
{
    protected const EXTENSIONS = [];

    /** @var string[] */
    protected $extensions;

    /** @var TemplateInterface|null */
    protected $loader;

    /**
     * {@inheritdoc}
     */
    public function withLoader(TemplateInterface $loader): RenderInterface
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withExtensions(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            $this->extensions[] = $extension;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return \array_unique($this->extensions);
    }
}
