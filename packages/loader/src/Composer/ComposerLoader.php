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

namespace BiuradPHP\Loader\Composer;

use ArrayIterator;
use BiuradPHP\Loader\Interfaces\ComposerInterface;
use Composer\Autoload\ClassLoader;
use Countable;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use UnexpectedValueException;

class ComposerLoader implements ComposerInterface, Countable, IteratorAggregate
{
    /** @var string */
    private $path;

    /** @var string */
    private $prefix;

    /** @var array */
    private $loadPaths = [];

    public function __construct(string $composerDirectory = null, string $prefix = 'composer.json')
    {
        $this->prefix = $prefix;
        $this->path = $composerDirectory ?? dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2) . '/';
    }

    /**
     * {@inheritdoc}
     *
     * @return ClassLoader
     * @throws RuntimeException
     */
    public function getClassLoader(bool $spl_functiion = false): ClassLoader
    {
        if ($spl_functiion !== false && function_exists('spl_autoload_functions')) {
            $autoloadFunctions = spl_autoload_functions();
            foreach ($autoloadFunctions as $autoloader) {
                if (!is_array($autoloader)) {
                    continue;
                }

                if (isset($autoloader[0]) && $autoloader[0] instanceof ClassLoader) {
                    return $autoloader[0];
                }
            }
        }

        if (file_exists(__DIR__ . '/../../../../autoload.php')) {
            return include __DIR__ . '/../../../../autoload.php';
        }

        if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
            return include __DIR__ . '/../../../vendor/autoload.php';
        }

        throw new RuntimeException('Cannot detect composer autoload. Please run composer install');
    }

    /**
     * Handles the vendor paths automaticatically.
     * You need not to add packages to the framework
     * The package just need to have composer.json file
     */
    public function processComposerPaths()
    {
        if (is_dir($this->path)) {
            /** @var RecursiveIteratorIterator|SplFileInfo[] $iterator */
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->path),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach (@$iterator as $entryPath) {
                if (
                    ($entryPath->isDir() && $entryPath->getFilename() != '.') &&
                    file_exists($entryPath->getPath() . '/' . $this->prefix)
                ) {
                    $this->loadPaths[] = $entryPath->getPath();
                }
            }

            return;
        }

        if (!file_exists($this->path) && pathinfo($this->path, PATHINFO_EXTENSION) !== 'json') {
            throw new UnexpectedValueException('Expected a json file containing packagists or composer\'s vendor directory');
        }

        foreach (json_decode(file_get_contents($this->path.'composer/installed.json'), true) as $composer) {
            $this->loadPaths[] = $this->path. $composer['name'];
        }
    }

    /**
     * Get All Found Paths
     */
    public function getPaths(): iterable
    {
        if (empty($this->loadPaths)) {
            $this->processComposerPaths();
        }

        return new ArrayIterator($this->loadPaths);
    }

    /**
     * Alias to getPaths().
     *
     * @return iterable
     */
    public function getIterator(): iterable
    {
        return $this->getPaths();
    }

    public function count()
    {
        return count($this->loadPaths);
    }

    /**
     * Get the full path of composer vendor directory
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
