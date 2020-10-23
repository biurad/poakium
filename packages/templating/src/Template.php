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

namespace Biurad\UI;

use Biurad\UI\Exceptions\RenderException;
use Biurad\UI\Interfaces\LoaderInterface;
use Biurad\UI\Interfaces\RenderInterface;
use Biurad\UI\Interfaces\StorageInterface;
use Biurad\UI\Interfaces\TemplateInterface;

final class Template implements TemplateInterface
{
    /** @var LoaderInterface */
    private $loader;

    /** @var RenderInterface[] */
    private $renders;

    /** @var array<string,mixed> */
    private $globals = [];

    /**
     * @param StorageInterface  $storage
     * @param null|Profile      $profile
     * @param RenderInterface[] $renders An array of RenderInterface instances to add
     */
    public function __construct(StorageInterface $storage, ?Profile $profile = null, array $renders = [])
    {
        $this->loader = new Loader($storage, $profile);

        foreach ($renders as $render) {
            $this->addRender($render);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal(string $name, $value): void
    {
        $this->globals[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function addNamespace(string $namespace, $hints): void
    {
        $this->loader->addNamespace($namespace, $hints);
    }

    /**
     * {@inheritdoc}
     */
    public function addRender(RenderInterface $render): void
    {
        $this->renders[] = $render->withLoader($this->loader);
    }

    /**
     * Get all associated view engines.
     *
     * @return RenderInterface[]
     */
    public function getRenders(): array
    {
        return $this->renders;
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $parameters = []): string
    {
        $this->addGlobal('template', $this);

        foreach ($this->renders as $engine) {
            if ($engine->getLoader()->exists($template)) {
                return $engine->render($template, \array_replace($parameters, $this->globals));
            }
        }

        throw new RenderException(
            \sprintf('No render engine is able to work with the template "%s".', $template)
        );
    }
}
