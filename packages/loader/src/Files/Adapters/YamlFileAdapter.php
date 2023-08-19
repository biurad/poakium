<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Loader\Files\Adapters;

use Biurad\Loader\Exceptions\FileGeneratingException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reading and generating Yaml/Yml files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class YamlFileAdapter extends AbstractAdapter
{
    /** @var callable YAML decoder callback. */
    private $yamlDecoder;

    /** @var callable YAML encoder callback. */
    private $yamlEncoder;

    /**
     * Constructor.
     *
     * @param callable             $yamlDecoder
     * @param callable|string|null $yamlEncoder
     */
    public function __construct(callable $yamlDecoder = null, callable $yamlEncoder = null)
    {
        // Try native PECL YAML PHP extension if available.
        $this->yamlDecoder = $yamlDecoder ?? \class_exists(Yaml::class) ? [new Yaml(), 'parse'] : (\function_exists('yaml_parse') ? 'yaml_parse' : null);
        $this->yamlEncoder = $yamlEncoder ?? \class_exists(Yaml::class) ? [new Yaml(), 'dump'] : (\function_exists('yaml_emit') ? 'yaml_emit' : null);
    }

    public function supports(string $file): bool
    {
        return \in_array(\strtolower(\pathinfo($file, \PATHINFO_EXTENSION)), ['yml', 'yaml'], true);
    }

    /**
     * Set callback for decoding YAML.
     *
     * @param callable $yamlDecoder the decoder to set
     */
    public function setYamlDecoder(callable $yamlDecoder): self
    {
        $this->yamlDecoder = $yamlDecoder;

        return $this;
    }

    /**
     * Get callback for decoding YAML.
     */
    public function getYamlDecoder(): callable
    {
        return $this->yamlDecoder;
    }

    /**
     * Get callback for decoding YAML.
     */
    public function getYamlEncoder(): callable
    {
        return $this->yamlEncoder;
    }

    /**
     * Set callback for decoding YAML.
     *
     * @param callable $yamlEncoder the decoder to set
     */
    public function setYamlEncoder(callable $yamlEncoder): self
    {
        $this->yamlEncoder = $yamlEncoder;

        return $this;
    }

    /**
     * Reads configuration from YAML\YML data.
     *
     * @throws \RuntimeException
     */
    protected function processFrom(string $string): array
    {
        if (null === $this->getYamlDecoder()) {
            throw new \RuntimeException('You didn\'t specify a Yaml\YML callback decoder');
        }

        if (empty($string)) {
            return [];
        }

        $config = $this->getYamlDecoder()($string);

        if (null === $config) {
            throw new \RuntimeException('Error parsing YAML\YML data');
        }

        return (array) $config;
    }

    /**
     * Generates configuration in YAML\YML format.
     *
     * @throws FileGeneratingException
     */
    protected function processDump(array $config): string
    {
        if (null === $this->getYamlEncoder()) {
            throw new FileGeneratingException("You didn't specify a Yaml callback encoder");
        }

        $config = $this->getYamlEncoder()($config, 2, 2);

        if (null === $config) {
            throw new FileGeneratingException('Error generating YAML data');
        }

        return $config;
    }
}
