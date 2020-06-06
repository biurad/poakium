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

namespace BiuradPHP\Loader;

use BiuradPHP\Loader\Interfaces\LoaderInterface;

/**
 * GlobFileLoader loads files from a glob pattern.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class GlobFileLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, string $type = null)
    {
        $globBrace = \defined('GLOB_BRACE') ? GLOB_BRACE : 0;
        $paths = null;

        if ($globBrace || false === strpos($resource, '{')) {
            $paths = glob($resource, GLOB_NOSORT | $globBrace);
        } elseif (false === strpos($resource, '\\') || !preg_match('/\\\\[,{}]/', $resource)) {
            foreach ($this->expandGlob($resource) as $pattern) {
                $paths[] = glob($pattern, GLOB_NOSORT);
            }

            $paths = array_merge(...$paths);
        }

        if (null !== $paths) {
            sort($paths);
            foreach ($paths as $path) {
                if (is_file($path)) {
                    yield $path => new \SplFileInfo($path);
                }
                if (!is_dir($path)) {
                    continue;
                }
                if ($this->forExclusion) {
                    yield $path => new \SplFileInfo($path);
                    continue;
                }

                $files = iterator_to_array(new \RecursiveIteratorIterator(
                    new \RecursiveCallbackFilterIterator(
                        new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                        function (\SplFileInfo $file, $path) {
                            return '.' !== $file->getBasename()[0];
                        }
                    ),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ));
                uasort($files, 'strnatcmp');

                foreach ($files as $path => $info) {
                    if ($info->isFile()) {
                        yield $path => $info;
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null): bool
    {
        return 'glob' === $type && is_string($resource);
    }

    private function expandGlob(string $pattern): array
    {
        $segments = preg_split('/\{([^{}]*+)\}/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        $paths = [$segments[0]];
        $patterns = [];

        for ($i = 1; $i < \count($segments); $i += 2) {
            $patterns = [];

            foreach (explode(',', $segments[$i]) as $s) {
                foreach ($paths as $p) {
                    $patterns[] = $p.$s.$segments[1 + $i];
                }
            }

            $paths = $patterns;
        }

        $j = 0;
        foreach ($patterns as $i => $p) {
            if (false !== strpos($p, '{')) {
                $p = $this->expandGlob($p);
                array_splice($paths, $i + $j, 1, $p);
                $j += \count($p) - 1;
            }
        }

        return $paths;
    }
}
