<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  Scaffolds Maker
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/scaffoldsmaker
 * @since     Version 0.1
 */

namespace BiuradPHP\Scaffold;

use Doctrine\Common\Inflector\Inflector;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class HelperUtil
{
    /**
     * Looks for suffixes in strings in a case-insensitive way.
     */
    public static function hasSuffix(string $value, string $suffix): bool
    {
        return 0 === strcasecmp($suffix, substr($value, -\strlen($suffix)));
    }

    /**
     * Ensures that the given string ends with the given suffix. If the string
     * already contains the suffix, it's not added twice. It's case-insensitive
     * (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'FooCommand').
     */
    public static function addSuffix(string $value, string $suffix): string
    {
        return self::removeSuffix($value, $suffix).$suffix;
    }

    /**
     * Ensures that the given string doesn't end with the given suffix. If the
     * string contains the suffix multiple times, only the last one is removed.
     * It's case-insensitive (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'Foo'.
     */
    public static function removeSuffix(string $value, string $suffix): string
    {
        return self::hasSuffix($value, $suffix) ? substr($value, 0, -\strlen($suffix)) : $value;
    }

    /**
     * Transforms the given string into the format commonly used by PHP classes,
     * (e.g. `app:do_this-and_that` -> `AppDoThisAndThat`) but it doesn't check
     * the validity of the class name.
     */
    public static function asClassName(string $value, string $suffix = ''): string
    {
        $value = trim($value);
        $value = str_replace(['-', '_', '.', ':'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        $value = ucfirst($value);
        $value = self::addSuffix($value, $suffix);

        return $value;
    }

    /**
     * Transforms the given string into the format commonly used by Twig variables
     * (e.g. `BlogPostType` -> `blog_post_type`).
     */
    public static function asTwigVariable(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
        $value = preg_replace('/(?<=\\w)([A-Z])/', '_$1', $value);
        $value = preg_replace('/_{2,}/', '_', $value);
        $value = strtolower($value);

        return $value;
    }

    public static function asLowerCamelCase(string $str): string
    {
        return lcfirst(self::asCamelCase($str));
    }

    public static function asCamelCase(string $str): string
    {
        return strtr(ucwords(strtr($str, ['_' => ' ', '.' => ' ', '\\' => ' '])), [' ' => '']);
    }

    public static function asRoutePath(string $value): string
    {
        return '/'.str_replace('_', '/', self::asTwigVariable($value));
    }

    public static function asRouteName(string $value): string
    {
        return self::asTwigVariable($value);
    }

    public static function asSnakeCase(string $value): string
    {
        return self::asTwigVariable($value);
    }

    public static function asCommand(string $value): string
    {
        return str_replace('_', '-', self::asTwigVariable($value));
    }

    public static function asEventMethod(string $eventName): string
    {
        return sprintf('on%s', self::asClassName($eventName));
    }

    public static function getShortClassName(string $fullClassName): string
    {
        if (empty(self::getNamespace($fullClassName))) {
            return $fullClassName;
        }

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }

    public static function getNamespace(string $fullClassName): string
    {
        return substr($fullClassName, 0, strrpos($fullClassName, '\\'));
    }

    public static function asFilePath(string $value): string
    {
        $value = str_replace('_', '.', trim($value));
        $value = preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $value);
        $value = strtolower(str_replace('\\', '/', $value));

        return $value;
    }

    public static function singularCamelCaseToPluralCamelCase(string $camelCase): string
    {
        $snake = self::asSnakeCase($camelCase);
        $words = explode('_', $snake);
        $words[\count($words) - 1] = Inflector::pluralize($words[\count($words) - 1]);
        $reSnaked = implode('_', $words);

        return self::asLowerCamelCase($reSnaked);
    }

    public static function pluralCamelCaseToSingular(string $camelCase): string
    {
        $snake = self::asSnakeCase($camelCase);
        $words = explode('_', $snake);
        $words[\count($words) - 1] = Inflector::singularize($words[\count($words) - 1]);
        $reSnaked = implode('_', $words);

        return self::asLowerCamelCase($reSnaked);
    }

    public static function getRandomTerm(): string
    {
        $adjectives = [
            'tiny',
            'delicious',
            'gentle',
            'agreeable',
            'brave',
            'orange',
            'grumpy',
            'fierce',
            'victorious',
        ];
        $nouns = [
            'elephant',
            'pizza',
            'jellybean',
            'chef',
            'puppy',
            'gnome',
            'kangaroo',
        ];

        return sprintf('%s %s', $adjectives[array_rand($adjectives)], $nouns[array_rand($nouns)]);
    }

    /**
     * Checks if the given name is a valid PHP variable name.
     *
     * @see http://php.net/manual/en/language.variables.basics.php
     *
     * @param $name string
     *
     * @return bool
     */
    public static function isValidPhpVariableName($name)
    {
        return (bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches);
    }

    public static function areClassesAlphabetical(string $class1, string $class2)
    {
        $arr1 = [$class1, $class2];
        $arr2 = [$class1, $class2];
        sort($arr2);

        return $arr1[0] == $arr2[0];
    }

    public static function asHumanWords(string $variableName): string
    {
        return implode(' ', preg_split('/(?=[A-Z])/', $variableName));
    }

    /**
     * @param string $annotationClass The annotation: e.g. "@ORM\Column"
     * @param array  $options         Key-value pair of options for the annotation
     *
     * @return string
     */
    public static function buildAnnotationLine(string $annotationClass, array $options)
    {
        $formattedOptions = array_map(function ($option, $value) {
            if (\is_array($value)) {
                if (!isset($value[0])) {
                    return sprintf('%s={%s}', $option, implode(', ', array_map(function ($val, $key) {
                        return sprintf('"%s" = %s', $key, $this->quoteAnnotationValue($val));
                    }, $value, array_keys($value))));
                }

                return sprintf('%s={%s}', $option, implode(', ', array_map(function ($val) {
                    return $this->quoteAnnotationValue($val);
                }, $value)));
            }

            return sprintf('%s=%s', $option, $this->quoteAnnotationValue($value));
        }, array_keys($options), array_values($options));

        return sprintf('%s(%s)', $annotationClass, implode(', ', $formattedOptions));
    }

    private static function quoteAnnotationValue($value)
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if (\is_int($value) || '0' === $value) {
            return $value;
        }

        if (\is_array($value)) {
            throw new \Exception('Invalid value: loop before quoting.');
        }

        return sprintf('"%s"', $value);
    }
}
