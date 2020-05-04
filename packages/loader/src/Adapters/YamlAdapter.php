<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  LoaderManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/biurad-loader
 * @since     Version 0.1
 */

namespace BiuradPHP\Loader\Adapters;

use RuntimeException;
use InvalidArgumentException;
use BiuradPHP\Loader\Interfaces\AdapterInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Reading and generating Yaml/Yml files.
 *
 * @author Zend Technologies USA Inc <http://www.zend.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class YamlAdapter implements AdapterInterface
{
    /**
     * Directory of the YAML file.
     *
     * @var string
     */
    private $directory;

    /**
     * YAML decoder callback.
     *
     * @var callable
     */
    private $yamlDecoder;

    /**
     * YAML encoder callback.
     *
     * @var callable
     */
    private $yamlEncoder;

    /**
     * Constructor.
     *
     * @param callable             $yamlDecoder
     * @param callable|string|null $yamlEncoder
     */
    public function __construct($yamlDecoder = null, $yamlEncoder = null)
    {
        if ($yamlDecoder !== null && $yamlEncoder !== null) {
            $this->setYamlDecoder($yamlDecoder);
            $this->setYamlEncoder($yamlEncoder);
        } else {
            if (class_exists(Yaml::class) && !function_exists('yaml_emit')) {
                $this->setYamlDecoder([new Yaml(), 'parse']);
                $this->setYamlEncoder([new Yaml(), 'dump']);
            }
            // Try native PECL YAML PHP extension first if available.
            if (function_exists('yaml_parse')) {
                $this->setYamlDecoder('yaml_parse');
            }
            if (function_exists('yaml_emit')) {
                $this->setYamlEncoder('yaml_emit');
            }
        }
    }

    /**
     * Set callback for decoding YAML.
     *
     * @param string|callable $yamlDecoder the decoder to set
     *
     * @return self
     *
     * @throws RuntimeException
     */
    public function setYamlDecoder($yamlDecoder)
    {
        if (!is_callable($yamlDecoder)) {
            throw new RuntimeException(
                'Invalid parameter to setYamlDecoder() - must be callable'
            );
        }
        $this->yamlDecoder = $yamlDecoder;

        return $this;
    }

    /**
     * Get callback for decoding YAML.
     *
     * @return callable
     */
    public function getYamlDecoder()
    {
        return $this->yamlDecoder;
    }

    /**
     * Get callback for decoding YAML.
     *
     * @return callable
     */
    public function getYamlEncoder()
    {
        return $this->yamlEncoder;
    }

    /**
     * Set callback for decoding YAML.
     *
     * @param callable $yamlEncoder the decoder to set
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public function setYamlEncoder($yamlEncoder)
    {
        if (!is_callable($yamlEncoder)) {
            throw new InvalidArgumentException('Invalid parameter to setYamlEncoder() - must be callable');
        }
        $this->yamlEncoder = $yamlEncoder;

        return $this;
    }

    /**
     * Reads configuration from YAML\YML file.
     *
     * @param string $filename
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public function fromFile(string $filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new RuntimeException(sprintf(
                "File '%s' doesn't exist or not readable",
                $filename
            ));
        }

        if (null === $this->getYamlDecoder()) {
            throw new RuntimeException("You didn't specify a Yaml\Yml callback decoder");
        }

        $this->directory = dirname($filename);

        $config = $this->yamlBound($this->getYamlDecoder(), file_get_contents($filename));
        if (null === $config) {
            throw new RuntimeException('Error parsing YAML\YML file');
        }

        return (array) $config;
    }

    /**
     * Reads configuration from YAML\YML data.
     *
     * @param string $string
     *
     * @return array|bool
     *
     * @throws RuntimeException
     */
    public function fromString($string)
    {
        if (null === $this->getYamlDecoder()) {
            throw new RuntimeException("You didn't specify a Yaml\YML callback decoder");
        }
        if (empty($string)) {
            return [];
        }

        $this->directory = null;

        $config = $this->yamlBound($this->getYamlDecoder(), $string);
        if (null === $config) {
            throw new RuntimeException("Error parsing YAML\YML data");
        }

        return (array) $config;
    }

    /**
     * Generates configuration in YAML\YML format.
     *
     * @param array $config
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function dump($config): string
    {
        if (null === $this->getYamlEncoder()) {
            throw new RuntimeException("You didn't specify a Yaml callback encoder");
        }

        $config = $this->yamlBound($this->getYamlEncoder(), $config);

        if (null === $config) {
            throw new RuntimeException('Error generating YAML data');
        }

        return $config;
    }

    /**
     * @param callable|string $function
     * @param mixed $arguments
     *
     * @return mixed
     */
    private function yamlBound(callable $function, $arguments)
    {
        return $function($arguments);
    }
}
