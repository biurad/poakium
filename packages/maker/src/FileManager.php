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

namespace BiuradPHP\Scaffold;

use Composer\Autoload\ClassLoader;
use Exception;
use InvalidArgumentException;
use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
class FileManager
{
    private $rootDirectory;

    private $rootNamespace;

    /** @var SymfonyStyle */
    private $io;

    public function __construct(string $rootDirectory, string $rootNamespace)
    {
        $this->rootNamespace = [
            'psr0' => \rtrim($rootNamespace, '\\'),
            'psr4' => \rtrim($rootNamespace, '\\') . '\\',
        ];
        $this->rootDirectory = \rtrim($this->realPath($this->normalizeSlashes($rootDirectory)), '/');
    }

    public function setIO(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        \ob_start();
        \extract($parameters, \EXTR_SKIP);

        include $templatePath;

        return \ob_get_clean();
    }

    public function dumpFile(string $filename, string $content, string $name = null): void
    {
        $absolutePath    = $this->absolutizePath($filename);
        $newFile         = !$this->fileExists($filename);
        $existingContent = $newFile ? '' : \file_get_contents($absolutePath);

        $comment = $newFile ? 'created' : 'updated';
        $type    = $newFile ? 'blue' : 'yellow';

        if ($existingContent === $content) {
            [$comment, $type] = ['no changes', 'green'];
        } else {
            FileSystem::write($absolutePath, $content);
        }

        if ($this->io) {
            $this->io->block(\sprintf(
                'Declaration of \'%s\' has [%s]: %s',
                $name ?? 'scaffold',
                $comment,
                $this->relativizePath($filename)
            ), 'OK', "fg=black;bg=$type", ' ', true);
        }
    }

    public function fileExists($path): bool
    {
        return \file_exists($this->absolutizePath($path));
    }

    /**
     * Attempts to make the path relative to the root directory.
     *
     * @param string $absolutePath
     *
     * @throws Exception
     */
    public function relativizePath($absolutePath): string
    {
        $absolutePath = $this->normalizeSlashes($absolutePath);

        // see if the path is even in the root
        if (false === \strpos($absolutePath, $this->rootDirectory)) {
            return $absolutePath;
        }

        $absolutePath = $this->realPath($absolutePath);

        // str_replace but only the first occurrence
        $relativePath = \ltrim(\implode('', \explode($this->rootDirectory, $absolutePath, 2)), '/');

        if (0 === \strpos($relativePath, './')) {
            $relativePath = \substr($relativePath, 2);
        }

        return \is_dir($absolutePath) ? \rtrim($relativePath, '/') . '/' : $relativePath;
    }

    public function getFileContents(string $path): string
    {
        if (!$this->fileExists($path)) {
            throw new InvalidArgumentException(\sprintf('Cannot find file "%s"', $path));
        }

        return \file_get_contents($this->absolutizePath($path));
    }

    public function absolutizePath($path): string
    {
        if (0 === \strpos($path, '/')) {
            return $path;
        }

        // support windows drive paths: C:\ or C:/
        if (1 === \strpos($path, ':\\') || 1 === \strpos($path, ':/')) {
            return $path;
        }

        return \sprintf('%s/%s', $this->rootDirectory, $path);
    }

    /**
     * Resolve '../' in paths (like real_path), but for non-existent files.
     *
     * @param string $absolutePath
     */
    public function realPath($absolutePath): string
    {
        $finalParts   = [];
        $currentIndex = -1;

        $absolutePath = $this->normalizeSlashes($absolutePath);

        foreach (\explode('/', $absolutePath) as $pathPart) {
            if ('..' === $pathPart) {
                // we need to remove the previous entry
                if (-1 === $currentIndex) {
                    throw new Exception(
                        \sprintf('Problem making path relative - is the path "%s" absolute?', $absolutePath)
                    );
                }

                unset($finalParts[$currentIndex]);
                --$currentIndex;

                continue;
            }

            $finalParts[] = $pathPart;
            ++$currentIndex;
        }

        $finalPath = \implode('/', $finalParts);
        // Normalize: // => /
        // Normalize: /./ => /
        $finalPath = \str_replace(['//', '/./'], '/', $finalPath);

        return $finalPath;
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    public function getRelativePathForFutureClass(string $className)
    {
        if (($classLoader = $this->findComposerClassLoader()) instanceof RobotLoader) {
            foreach ($classLoader->getIndexedClasses() as $class => $path) {
                if (0 === \strpos($className, HelperUtil::getNamespace($class))) {
                    return $this->joinPathChunks([
                        $this->realPath(\dirname($path)),
                        $this->normalizeSlashes(
                            \substr($className, \strlen(HelperUtil::getNamespace($class) . '\\')) . '.php'
                        ),
                    ], '/');
                }
            }
        }

        // lookup is obviously modeled off of Composer's autoload logic
        foreach ($classLoader->getPrefixesPsr4() as $prefix => $paths) {
            if (0 === \strpos($className, $prefix)) {
                return $this->joinPathChunks([
                    $this->realPath($paths[0]),
                    $this->normalizeSlashes(\substr($className, \strlen($prefix)) . '.php'),
                ], '/');
            }
        }

        foreach ($classLoader->getPrefixes() as $prefix => $paths) {
            if (0 === \strpos($className, $prefix)) {
                return $this->joinPathChunks([
                    $this->realPath($paths[0]),
                    $this->normalizeSlashes($className . '.php'),
                ], '/');
            }
        }

        if ($classLoader->getFallbackDirsPsr4()) {
            return $this->joinPathChunks([
                $this->realPath($classLoader->getFallbackDirsPsr4()[0]),
                $this->normalizeSlashes($className . '.php'),
            ], '/');
        }

        if ($classLoader->getFallbackDirs()) {
            return $this->joinPathChunks([
                $this->realPath($classLoader->getFallbackDirs()[0]),
                $this->normalizeSlashes($className . '.php'),
            ], '/');
        }

        return $this->joinPathChunks([
            $this->realPath($this->rootDirectory),
            $this->normalizeSlashes($className . '.php'),
        ], '/');
    }

    public function getNamespacePrefixForClass(string $className): string
    {
        if (($classLoader = $this->findComposerClassLoader()) instanceof RobotLoader) {
            foreach ($classLoader->getIndexedClasses() as $class => $path) {
                if (0 === \strpos($className, HelperUtil::getNamespace($class))) {
                    return HelperUtil::getNamespace($class) . '\\';
                }
            }
        }

        foreach ($this->findComposerClassLoader()->getPrefixesPsr4() as $prefix => $paths) {
            if (0 === \strpos($className, $prefix)) {
                return $prefix;
            }
        }

        return '';
    }

    /**
     * @return null|ClassLoader|RobotLoader
     */
    private function findComposerClassLoader()
    {
        $autoloadFunctions = \spl_autoload_functions();

        foreach ($autoloadFunctions as $autoloader) {
            if (!\is_array($autoloader)) {
                continue;
            }

            if (isset($autoloader[0]) && $autoloader[0] instanceof ClassLoader) {
                $classLoader = $autoloader[0];
            }

            if (isset($classLoader)) {
                foreach ($classLoader->getPrefixesPsr4() as $prefix => $paths) {
                    if (0 === \strpos($this->rootNamespace['psr4'], $prefix)) {
                        return $classLoader;
                    }
                }

                foreach ($classLoader->getPrefixes() as $prefix => $paths) {
                    if (0 === \strpos($this->rootNamespace['psr0'], $prefix)) {
                        return $classLoader;
                    }
                }
            }

            if (isset($autoloader[0]) && $autoloader[0] instanceof RobotLoader) {
                $classLoader = $autoloader[0];

                foreach ($classLoader->getIndexedClasses() as $class => $path) {
                    if (0 === \strpos($this->rootNamespace['psr0'], HelperUtil::getNamespace($class))) {
                        return $classLoader;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array  $chunks
     * @param string $joint
     *
     * @return string
     */
    private function joinPathChunks(array $chunks, string $joint): string
    {
        $firstChunkIterated = false;
        $joinedPath         = '';

        foreach ($chunks as $chunk) {
            if (!$firstChunkIterated) {
                $firstChunkIterated = true;
                $joinedPath         = $chunk;
            } else {
                $joinedPath = \rtrim($joinedPath, $joint) . $joint . \ltrim($chunk, $joint);
            }
        }

        return $joinedPath;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function normalizeSlashes(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }
}
