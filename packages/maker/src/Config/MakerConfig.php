<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Scaffold\Config;

use BiuradPHP\Scaffold\Exceptions\MakerException;

/**
 * Configuration for default scaffolder namespaces and other rendering options.
 */
class MakerConfig
{
    /** @var array */
    protected $config = [
        'header'         => [],
        'root_directory' => '',
        'view_directory' => '',
        'namespace'      => '',
        'declarations'   => [],
    ];

    /**
     * At this moment on array based configs can be supported.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function headerLines(): array
    {
        return \unserialize(\current($this->config['header']));
    }

    /**
     * @return string
     */
    public function baseDirectory(): string
    {
        return $this->config['root_directory'];
    }

    /**
     * @return string
     */
    public function baseNamespace(): string
    {
        return \trim($this->config['namespace'], '\\');
    }

    /**
     * @param string $element
     *
     * @throws MakerException
     *
     * @return string
     */
    public function declarationClass(string $element): string
    {
        $class = $this->getOption($element, 'class');

        if (empty($class)) {
            throw new MakerException(
                "Unable to scaffold '{$element}', no declaration class found"
            );
        }

        return $class;
    }

    /**
     * Declaration options.
     *
     * @param string $element
     *
     * @return array
     */
    public function declarationOptions(string $element): array
    {
        return $this->getOption($element, 'options');
    }

    /**
     * Get all Scaffold Declarations.
     *
     * @return array
     */
    public function getDeclarations(): array
    {
        return $this->config['declarations'];
    }

    /**
     * Add a new scaffold maker to config
     *
     * @param string $element
     * @param string $declaration
     * @param string $namespace
     * @param string $postfix
     * @param array  $options
     */
    public function addDeclaration(
        string $element,
        string $declaration,
        string $namespace,
        string $postfix = '',
        array $options = []
    ): void {
        $this->config['declarations'][$element] = [
            'namspace' => $namespace,
            'postfix'  => $postfix,
            'class'    => $declaration,
            'options'  => $options,
        ];
    }

    /**
     * @param string $element
     * @param string $section
     *
     * @return mixed
     */
    public function getOption(string $element, string $section)
    {
        if (!isset($this->config['declarations'][$element])) {
            throw new MakerException("Undefined declaration '{$element}'.");
        }

        if (\array_key_exists($section, $this->config['declarations'][$element])) {
            return $this->config['declarations'][$element][$section];
        }

        throw new MakerException(\sprintf('%s doesn\'t exist in %s declaration', $section, $element));
    }
}
