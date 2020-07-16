<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Cache;

use ErrorException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

class Preloader
{
    private const PRELOAD_KEY_CACHE = 'preload_statistics';

    /**
     * Append an array of classes to preload.
     *
     * @param string        $file
     * @param array<string> $list
     */
    public static function append(string $file, array $list): void
    {
        if (!\file_exists($file)) {
            throw new LogicException(\sprintf('File "%s" does not exist.', $file));
        }

        $cacheDir = \dirname($file);
        $classes  = [];

        foreach ($list as $item) {
            if (0 === \strpos($item, $cacheDir)) {
                \file_put_contents(
                    $file,
                    \sprintf("require_once __DIR__.%s;\n", \var_export(\substr($item, \strlen($cacheDir)), true)),
                    \FILE_APPEND
                );

                continue;
            }

            $classes[] = \sprintf("\$classes[] = %s;\n", \var_export($item, true));
        }

        \file_put_contents(
            $file,
            \sprintf("\n\$classes = [];\n%sPreloader::preload(\$classes);\n", \implode('', $classes)),
            \FILE_APPEND
        );
    }

    /**
     * Gives some information's about opcache preloading.
     *
     * @param string $type of 'functions', 'scripts' or 'classes'
     *
     * @return null|array<string,mixed>
     */
    public static function getStatus(string $type): ?array
    {
        $opcacheStatus = (array) \opcache_get_status();

        return $opcacheStatus[self::PRELOAD_KEY_CACHE][$type] ?? null;
    }

    /**
     * Returns the opcache preload statistics
     *
     * @return null|array<string,mixed>
     */
    public static function getStatistics(): ?array
    {
        $opcacheStatus = (array) \opcache_get_status();

        return $opcacheStatus[self::PRELOAD_KEY_CACHE] ?? null;
    }

    /**
     * @param array<string> $classes
     */
    public static function preload(array $classes): void
    {
        \set_error_handler([__CLASS__, 'errorHandler']);

        $prev      = [];
        $preloaded = [];

        try {
            while ($prev !== $classes) {
                $prev = $classes;

                foreach ($classes as $c) {
                    if (!isset($preloaded[$c])) {
                        self::doPreload($c, $preloaded);
                    }
                }
                $classes = \array_merge(
                    \get_declared_classes(),
                    \get_declared_interfaces(),
                    \get_declared_traits() ?? []
                );
            }
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * @param string             $class
     * @param array<string,bool> $preloaded
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    private static function doPreload(string $class, array &$preloaded): void
    {
        if (isset($preloaded[$class]) || \in_array($class, ['self', 'static', 'parent'], true)) {
            return;
        }

        $preloaded[$class] = true;

        try {
            $r = new ReflectionClass($class);

            if ($r->isInternal()) {
                return;
            }

            $r->getConstants();
            $r->getDefaultProperties();

            if (\PHP_VERSION_ID >= 70400) {
                foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
                    if (null !== ($t = $p->getType()) && !$t->isBuiltin()) {
                        \assert($t instanceof ReflectionNamedType);
                        self::doPreload($t->getName(), $preloaded);
                    }
                }
            }

            foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                foreach ($m->getParameters() as $p) {
                    if ($p->isDefaultValueAvailable() && $p->isDefaultValueConstant()) {
                        $c = (string) $p->getDefaultValueConstantName();

                        if ($i = \strpos($c, '::')) {
                            self::doPreload(\substr($c, 0, $i), $preloaded);
                        }
                    }

                    if (null !== ($t = $p->getType()) && !$t->isBuiltin()) {
                        \assert($t instanceof ReflectionNamedType);
                        self::doPreload($t->getName(), $preloaded);
                    }
                }

                if (null !== ($t = $m->getReturnType()) && !$t->isBuiltin()) {
                    \assert($t instanceof ReflectionNamedType);
                    self::doPreload($t->getName(), $preloaded);
                }
            }
        } catch (ReflectionException $e) {
            // ignore missing classes
        }
    }

    private static function errorHandler(int $type, string $message, string $file, int $line): bool
    {
        if (\error_reporting() & $type) {
            if (__FILE__ !== $file) {
                throw new ErrorException($message, 0, $type, $file, $line);
            }

            throw new ReflectionException($message);
        }

        return false;
    }
}
