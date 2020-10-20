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

use Biurad\UI\Exceptions\RenderException;
use Biurad\UI\Interfaces\LoaderInterface;
use Biurad\UI\Interfaces\RenderInterface;

/**
 * Render engine with ability to switch environment and loader.
 */
abstract class AbstractRender implements RenderInterface
{
    protected const EXTENSIONS = [];

    /** @var string[] */
    protected $extensions;

    /** @var LoaderInterface */
    protected $loader;

    /**
     * {@inheritdoc}
     */
    public function withLoader(LoaderInterface $loader): RenderInterface
    {
        $render         = clone $this;
        $render->loader = $loader->withExtensions($this->extensions ?? static::EXTENSIONS);

        return $render;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoader(): LoaderInterface
    {
        if (null === $this->loader) {
            throw new RenderException('No associated loader found');
        }

        return $this->loader;
    }
}
