<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Loader\Files\Adapters;

use BiuradPHP\Loader\Exceptions\FileLoadingException;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMText;
use Exception;
use LogicException;
use RuntimeException;
use XMLWriter;

/**
 * Reading and generating XML/Html files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
final class XmlFileAdapter extends AbstractAdapter
{
    public function __construct()
    {
        if (!\extension_loaded('dom') && !\extension_loaded('xmlwriter')) {
            throw new LogicException('Extension DOM and XmlWriter is required.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return \in_array(\strtolower(\pathinfo($file, \PATHINFO_EXTENSION)), ['htm', 'html', 'xml'], true);
    }

    /**
     * Parses an XML string.
     *
     * @param string               $content          An XML string
     * @param null|callable|string $schemaOrCallable An XSD schema file path, a callable, or null to disable validation
     *
     * @throws FileLoadingException When parsing of XML file returns error
     * @throws InvalidXmlException  When parsing of XML with schema or callable produces any errors
     *                              unrelated to the XML parsing itself
     * @throws RuntimeException     When DOM extension is missing
     *
     * @return DOMDocument
     */
    public function parse(string $content, $schemaOrCallable = null): DOMDocument
    {
        $internalErrors  = \libxml_use_internal_errors(true);
        $disableEntities = \libxml_disable_entity_loader(true);
        \libxml_clear_errors();

        $dom                  = new DOMDocument();
        $dom->validateOnParse = true;

        if (!$dom->loadXML($content, \LIBXML_NONET | (\defined('LIBXML_COMPACT') ? \LIBXML_COMPACT : 0))) {
            \libxml_disable_entity_loader($disableEntities);

            $dom->loadHTML($content);
        }

        $dom->normalizeDocument();

        \libxml_use_internal_errors($internalErrors);
        \libxml_disable_entity_loader($disableEntities);

        if (null !== $schemaOrCallable) {
            $internalErrors = \libxml_use_internal_errors(true);
            \libxml_clear_errors();

            $e = null;

            if (\is_callable($schemaOrCallable)) {
                try {
                    $valid = $schemaOrCallable($dom, $internalErrors);
                } catch (Exception $e) {
                    $valid = false;
                }
            } elseif (!\is_array($schemaOrCallable) && \is_file((string) $schemaOrCallable)) {
                $schemaSource = \file_get_contents((string) $schemaOrCallable);
                $valid        = @$dom->schemaValidateSource($schemaSource);
            } else {
                \libxml_use_internal_errors($internalErrors);

                throw new FileLoadingException(
                    'The schemaOrCallable argument has to be a valid path to XSD file or callable.'
                );
            }

            if (!$valid) {
                $messages = $this->getXmlErrors($internalErrors);

                if (empty($messages)) {
                    throw new RuntimeException('The XML is not valid.', 0, $e);
                }

                throw new FileLoadingException(\implode("\n", $messages), 0, $e);
            }
        }

        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);

        return $dom;
    }

    /**
     * Converts a \DOMElement object to a PHP array.
     *
     * The following rules applies during the conversion:
     *
     *  * Each tag is converted to a key value or an array
     *    if there is more than one "value"
     *
     *  * The content of a tag is set under a "value" key (<foo>bar</foo>)
     *    if the tag also has some nested tags
     *
     *  * The attributes are converted to keys (<foo foo="bar"/>)
     *
     *  * The nested-tags are converted to keys (<foo><foo>bar</foo></foo>)
     *
     * @param DOMElement $element     A \DOMElement instance
     * @param bool       $checkPrefix Check prefix in an element or an attribute name
     *
     * @return mixed
     */
    public function convertDomElementToArray(DOMElement $element, bool $checkPrefix = true)
    {
        $prefix = (string) $element->prefix;
        $empty  = true;
        $config = [];

        foreach ($element->attributes as $name => $node) {
            if ($checkPrefix && !\in_array((string) $node->prefix, ['', $prefix], true)) {
                continue;
            }
            $config[$name] = $this->phpize($node->value);
            $empty         = false;
        }

        $nodeValue = false;

        foreach ($element->childNodes as $node) {
            if ($node instanceof DOMText) {
                if ('' !== \trim($node->nodeValue)) {
                    $nodeValue = \trim($node->nodeValue);
                    $empty     = false;
                }
            } elseif ($checkPrefix && $prefix != (string) $node->prefix) {
                continue;
            } elseif (!$node instanceof DOMComment) {
                $value = $this->convertDomElementToArray($node, $checkPrefix);

                $key = $node->localName;

                if (isset($config[$key])) {
                    if (!\is_array($config[$key]) || !\is_int(\key($config[$key]))) {
                        $config[$key] = [$config[$key]];
                    }
                    $config[$key][] = $value;
                } else {
                    $config[$key] = $value;
                }

                $empty = false;
            }
        }

        if (false !== $nodeValue) {
            $value = $this->phpize($nodeValue);

            if (\count($config)) {
                $config['value'] = $value;
            } else {
                $config = $value;
            }
        }

        return !$empty ? $config : null;
    }

    /**
     * Reads configuration from XML data.
     *
     * @param string $string
     *
     * @return array
     */
    protected function processFrom(string $string): array
    {
        $content = $this->parse($string);

        return $this->convertDomElementToArray($content->documentElement);
    }

    /**
     * Generates configuration in XML format,
     * Process the array|objects for dumping.
     *
     * @param array $config
     *
     * @return string
     */
    protected function processDump(array $config): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(\str_repeat(' ', 4));

        if (isset($config['head'], $config['body'])) {
            $writer->writeRaw("<!Doctype html>\n");
            $writer->startElement('html');
        } else {
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('loader-config');
        }

        foreach ($config as $sectionName => $data) {
            if (!\is_array($data)) {
                $writer->writeAttribute($sectionName, $this->xmlize($data));
            } else {
                $this->addBranch($sectionName, $data, $writer);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Add a branch to an XML/Html object recursively.
     *
     * @param string    $branchName
     * @param array     $config
     * @param XMLWriter $writer
     *
     * @throws RuntimeException
     */
    protected function addBranch($branchName, array $config, XMLWriter $writer): void
    {
        $branchType = null;

        foreach ($config as $key => $value) {
            if ($branchType === null) {
                if (\is_numeric($key)) {
                    $branchType = 'numeric';
                } else {
                    $writer->startElement($branchName);
                    $branchType = 'string';
                }
            } elseif ($branchType !== (\is_numeric($key) ? 'numeric' : 'string')) {
                throw new RuntimeException('Mixing of string and numeric keys is not allowed');
            }

            if ($branchType === 'numeric') {
                if (\is_array($value)) {
                    $this->addBranch($branchName, $value, $writer);
                } else {
                    $writer->writeElement($branchName, $this->xmlize($value));
                }
            } else {
                if (\is_array($value)) {
                    if (\array_key_exists('value', $value)) {
                        $newValue = $value['value'];
                        unset($value['value']);

                        $writer->startElement($key);
                        \array_walk_recursive($value, function ($item, $id) use (&$writer): void {
                            $writer->writeAttribute($id, $item);
                        });

                        $writer->writeRaw($this->xmlize($newValue));
                        $writer->endElement();
                        $writer->endAttribute();
                    } else {
                        $this->addBranch($key, $value, $writer);
                    }
                } else {
                    $writer->writeAttribute($key, $this->xmlize($value));
                }
            }
        }

        if ($branchType === 'string') {
            $writer->endElement();
        }
    }

    /**
     * Converts an xml value to a PHP type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function phpize($value)
    {
        $value          = (string) $value;
        $lowercaseValue = \strtolower($value);

        switch (true) {
            case 'null' === $lowercaseValue:
                return null;
            case \ctype_digit($value):
                $raw  = $value;
                $cast = (int) $value;

                return '0' == $value[0] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case isset($value[1]) && '-' === $value[0] && \ctype_digit(\substr($value, 1)):
                $raw  = $value;
                $cast = (int) $value;

                return '0' == $value[1] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case 'true' === $lowercaseValue:
                return true;
            case 'false' === $lowercaseValue:
                return false;
            case isset($value[1]) && '0b' == $value[0] . $value[1] && \preg_match('/^0b[01]*$/', $value):
                return \bindec($value);
            case \is_numeric($value):
                return '0x' === $value[0] . $value[1] ? \hexdec($value) : (float) $value;
            case \preg_match('/^0x[0-9a-f]++$/i', $value):
                return \hexdec($value);
            case \preg_match('/^[+-]?[0-9]+(\.[0-9]+)?$/', $value):
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Converts an PHP type to a Xml value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function xmlize($value): string
    {
        switch (true) {
            case null === $value:
                return 'null';
            case isset($value[1]) && '-' === $value[0] && \ctype_digit(\substr($value, 1)):
                $raw  = $value;
                $cast = (int) $value;

                return (string) 0 == $value[1] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case true === $value:
                return 'true';
            case false === $value:
                return 'false';
            case isset($value[1]) && '0b' == $value[0] . $value[1] && \preg_match('/^0b[01]*$/', (string) $value):
                return (string) \bindec($value);
            case \preg_match('/^0x[0-9a-f]++$/i', (string) $value):
                return \hexdec($value);
            case \preg_match('/^[+-]?[0-9]+(\.[0-9]+)?$/', (string) $value):
                return (string) (float) $value;
            default:
                return (string) $value;
        }
    }

    protected function getXmlErrors(bool $internalErrors)
    {
        $errors = [];

        foreach (\libxml_get_errors() as $error) {
            $errors[] = \sprintf(
                '[%s %s] %s (in %s - line %d, column %d)',
                \LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                \trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }

        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
