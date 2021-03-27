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
use Biurad\UI\Interfaces\HtmlInterface;
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

    /**
     * Load a php html template file.
     */
    protected static function loadHtml(string $file): ?string
    {
        if (!\str_starts_with($file, 'html:')) {
            return null;
        }

        $level = \ob_get_level();
        \ob_start(function () {
            return '';
        });

        try {
            $templateContent = require($file = \substr($file, 5));
            \ob_end_clean();

            if (!$templateContent instanceof HtmlInterface) {
                throw new RenderException(\sprintf('Could not render template file "%s" as it does not return a "%s" instance.', $file, HtmlInterface::class));
            }

            $content = (string) $templateContent;
        } catch (\Throwable $e) {
            while (\ob_get_level() > $level) {
                \ob_end_clean();
            }

            throw $e;
        }

        return $content;
    }
}
