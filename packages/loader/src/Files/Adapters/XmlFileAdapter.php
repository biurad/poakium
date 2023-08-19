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

use Biurad\Loader\Exceptions\FileLoadingException;

/**
 * Reading and generating XML/Html files.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class XmlFileAdapter extends AbstractAdapter
{
    public function __construct()
    {
        if (!\extension_loaded('dom') && !\extension_loaded('xmlwriter')) {
            throw new \LogicException('Extension DOM and XmlWriter is required.');
        }
    }

    public function supports(string $file): bool
    {
        return \in_array(\strtolower(\pathinfo($file, \PATHINFO_EXTENSION)), ['htm', 'html', 'xml'], true);
    }

    /**
     * Parses an XML string.
     *
     * @param string               $content          An XML string
     * @param callable|string|null $schemaOrCallable An XSD schema file path, a callable, or null to disable validation
     *
     * @throws FileLoadingException When parsing of XML file returns error
     *                              unrelated to the XML parsing itself
     * @throws \RuntimeException    When DOM extension is missing
     */
    public function parse(string $content, $schemaOrCallable = null): \DOMDocument
    {
        $internalErrors = \libxml_use_internal_errors(true);
        \libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;

        if (!$dom->loadXML($content, \LIBXML_NONET | (\defined('LIBXML_COMPACT') ? \LIBXML_COMPACT : 0))) {
            $dom->loadHTML($content, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        }

        $dom->normalizeDocument();
        \libxml_use_internal_errors($internalErrors);

        if (null !== $schemaOrCallable) {
            $internalErrors = \libxml_use_internal_errors(true);
            \libxml_clear_errors();

            $e = null;

            if (\is_callable($schemaOrCallable)) {
                try {
                    $valid = $schemaOrCallable($dom, $internalErrors);
                } catch (\Exception $e) {
                    $valid = false;
                }
            } elseif (!\is_array($schemaOrCallable) && \is_file((string) $schemaOrCallable)) {
                $schemaSource = \file_get_contents((string) $schemaOrCallable);
                $valid = @$dom->schemaValidateSource($schemaSource);
            } else {
                \libxml_use_internal_errors($internalErrors);

                throw new FileLoadingException('The schemaOrCallable argument has to be a valid path to XSD file or callable.');
            }

            if (!$valid) {
                $messages = $this->getXmlErrors($internalErrors);

                if (empty($messages)) {
                    throw new \RuntimeException('The XML is not valid.', 0, $e);
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
     * @param \DOMElement $element     A \DOMElement instance
     * @param bool        $checkPrefix Check prefix in an element or an attribute name
     */
    public function convertDomElementToArray(\DOMElement $element, bool $checkPrefix = true)
    {
        $prefix = (string) $element->prefix;
        $empty = true;
        $config = [];

        foreach ($element->attributes as $name => $node) {
            if ($checkPrefix && !\in_array((string) $node->prefix, ['', $prefix], true)) {
                continue;
            }
            $config[$name] = $this->phpize($node->value);
            $empty = false;
        }

        $nodeValue = false;

        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMText) {
                if ('' !== \trim($node->nodeValue)) {
                    $nodeValue = \trim($node->nodeValue);
                    $empty = false;
                }
            } elseif ($checkPrefix && $prefix != (string) $node->prefix) {
                continue;
            } elseif (!$node instanceof \DOMComment) {
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
     */
    protected function processFrom(string $string): array
    {
        $content = $this->parse($string);

        return $this->convertDomElementToArray($content->documentElement);
    }

    /**
     * Generates configuration in XML format,
     * Process the array|objects for dumping.
     */
    protected function processDump(array $config): string
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(\str_repeat(' ', 4));

        if (isset($config['head'], $config['body'])) {
            $writer->writeRaw("<!Doctype html>\n");
            $writer->startElement('html');
        } else {
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('config');
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
     * @param string $branchName
     *
     * @throws \RuntimeException
     */
    protected function addBranch($branchName, array $config, \XMLWriter $writer): void
    {
        $branchType = null;

        if ($isAttribute = \count($config) < 5) {
            foreach ($config as $v) {
                if (\is_scalar($v) || null === $v) {
                    continue;
                }

                $isAttribute = false;
                break;
            }
        }

        foreach ($config as $key => $value) {
            if ('int' === $branchType || \is_numeric($key)) {
                $branchType ??= 'int';
                if (\is_array($value)) {
                    $this->addBranch($branchName, $value, $writer);
                    continue;
                }

                $writer->writeElement($branchName, $this->xmlize($value));
                continue;
            }

            if (null === $branchType) {
                $branchType = 'string';
                $writer->startElement($branchName);
            }

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
                    continue;
                }

                $this->addBranch($key, $value, $writer);
                continue;
            }

            if ($isAttribute) {
                $writer->writeAttribute($key, $this->xmlize($value));
                continue;
            }

            $writer->startElement($key);
            $writer->writeRaw($this->xmlize($value));
            $writer->endElement();
        }

        if ('string' === $branchType) {
            $writer->endElement();
        }
    }

    /**
     * Converts an xml value to a PHP type.
     */
    protected function phpize($value)
    {
        $value = (string) $value;
        $lowercaseValue = \strtolower($value);

        switch (true) {
            case 'null' === $lowercaseValue:
                return null;
            case \ctype_digit($value):
                $raw = $value;
                $cast = (int) $value;

                return '0' == $value[0] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case isset($value[1]) && '-' === $value[0] && \ctype_digit(\substr($value, 1)):
                $raw = $value;
                $cast = (int) $value;

                return '0' == $value[1] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case 'true' === $lowercaseValue:
                return true;
            case 'false' === $lowercaseValue:
                return false;
            case isset($value[1]) && '0b' == $value[0].$value[1] && \preg_match('/^0b[01]*$/', $value):
                return \bindec($value);
            case \is_numeric($value):
                return '0x' === $value[0].$value[1] ? \hexdec($value) : (float) $value;
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
     */
    protected function xmlize($value): string
    {
        switch (true) {
            case null === $value:
                return 'null';
            case isset($value[1]) && '-' === $value[0] && \ctype_digit(\substr($value, 1)):
                $raw = $value;
                $cast = (int) $value;

                return (string) 0 == $value[1] ? \octdec($value) : (((string) $raw === (string) $cast) ? $cast : $raw);
            case true === $value:
                return 'true';
            case false === $value:
                return 'false';
            case isset($value[1]) && '0b' == $value[0].$value[1] && \preg_match('/^0b[01]*$/', (string) $value):
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
