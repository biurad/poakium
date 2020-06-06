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

namespace BiuradPHP\Loader\Locators;

use BiuradPHP\Loader\Exceptions\LoaderException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\VarDumper\Tests\Fixtures\NotLoadableClass;

/**
 * Can locate classes in a specified directory.
 *
 * @author Divine Niiquaye <divineibok@gmail.com>
 * @license BSD-3-Cluase
 */
final class ClassLocator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FileLoader */
    protected $finder = null;

    /**
     * @param FileLoader $finder
     */
    public function __construct(FileLocator $finder)
    {
        if (!\function_exists('token_get_all')) {
            throw new \LogicException('The Tokenizer extension is required for the class loading.');
        }

        $this->finder = $finder;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses($target = null): array
    {
        if (!empty($target) && (is_object($target) || is_string($target))) {
            $target = new \ReflectionClass($target);
        }

        $result = [];
        foreach ($this->availableReflections() as $class) {
            if (null === $class || $class === NotLoadableClass::class) {
                continue;
            }

            try {
                $reflection = $this->classReflection($class);
            } catch (LoaderException $e) {
                //Ignoring
                continue;
            }

            if (!$this->isTargeted($reflection, $target) || $reflection->isInterface()) {
                continue;
            }

            $result[$reflection->getName()] = $reflection;
        }

        gc_mem_caches();

        return $result;
    }

    /**
     * Find all the class and interface names in a given directory.
     *
     * @return array
     */
    public function findClasses(): array
    {
        $found = [];
        foreach ($this->finder->findFiles('php') as $file) {
            $found[] = $this->findClass(@$file);
        }

        return $found;
    }

    /**
     * Extract the class name from the file at the given path.
     *
     * @param  string  $path
     *
     * @return string|null
     */
    public function findClass(string $path): ?string
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($path));

        if (1 === \count($tokens) && T_INLINE_HTML === $tokens[0][0]) {
            if (null !== $this->logger) {
                // We are not analyzing php files which are meant for template purpose
                $this->logger->warning(sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?', $path), compact('path'));
            }

            return null;
        }

        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];

            if (!isset($token[1])) {
                continue;
            }

            if (in_array($token[0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
                if (null !== $this->logger) {
                    // We are not analyzing files which has includes, it's not safe to require such reflections
                    $this->logger->warning(sprintf("File `%s` has includes and excluded from analysis", $path), compact('path'));
                }

                continue;
            }

            if (true === $class && T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && T_STRING === $token[0]) {
                $namespace = $token[1];
                while (isset($tokens[++$i][1]) && \in_array($tokens[$i][0], [T_NS_SEPARATOR, T_STRING], true)) {
                    $namespace .= $tokens[$i][1];
                }
                $token = $tokens[$i];
            }

            if (T_CLASS === $token[0]) {
                // Skip usage of ::class constant and anonymous classes
                $skipClassToken = false;
                for ($j = $i - 1; $j > 0; --$j) {
                    if (!isset($tokens[$j][1])) {
                        break;
                    }

                    if (T_DOUBLE_COLON === $tokens[$j][0] || T_NEW === $tokens[$j][0]) {
                        $skipClassToken = true;
                        break;
                    } elseif (!\in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], true)) {
                        break;
                    }
                }

                if (!$skipClassToken) {
                    $class = true;
                }
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return null;
    }

    /**
     * Available file reflections. Generator.
     *
     * @return ReflectionClass[]|\Generator
     */
    protected function availableReflections(): \Generator
    {
        foreach ($this->findClasses() as $class) {
            yield $class;
        }
    }

    /**
     * Safely get class reflection, class loading errors will be blocked and reflection will be
     * excluded from analysis.
     *
     * @param string $class
     *
     * @return \ReflectionClass
     */
    protected function classReflection(string $class): \ReflectionClass
    {
        $loader = function ($class) {
            if ($class == LoaderException::class) {
                return;
            }

            throw new LoaderException("Class '{$class}' can not be loaded");
        };

        //To suspend class dependency exception
        spl_autoload_register($loader);

        try {
            //In some cases reflection can thrown an exception if class invalid or can not be loaded,
            //we are going to handle such exception and convert it soft exception
            return new \ReflectionClass($class);
        } catch (\Throwable $e) {
            if ($e instanceof LoaderException && $e->getPrevious() != null) {
                $e = $e->getPrevious();
            }

            if (null !== $this->logger) {
                $this->logger->error(
                    sprintf("%s: %s in %s:%s", $class, $e->getMessage(), $e->getFile(), $e->getLine()),
                    ['error' => $e]
                );
            }

            throw new LoaderException($e->getMessage(), $e->getCode(), $e);
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    /**
     * Get every class trait (including traits used in parents).
     *
     * @param string $class
     *
     * @return array
     */
    protected function fetchTraits(string $class): array
    {
        $traits = [];

        while ($class) {
            $traits = array_merge(class_uses($class), $traits);
            $class = get_parent_class($class);
        }

        //Traits from traits
        foreach (array_flip($traits) as $trait) {
            $traits = array_merge(class_uses($trait), $traits);
        }

        return array_unique($traits);
    }

    /**
     * Classes available in finder scope.
     *
     * @return array
     */
    protected function availableClasses(): array
    {
        $classes = [];

        foreach ($this->availableReflections() as $class) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                //Ignoring
                continue;
            }

            $classes[] = $reflection->getName();
        }

        return $classes;
    }

    /**
     * Check if given class targeted by locator.
     *
     * @param \ReflectionClass      $class
     * @param \ReflectionClass|null $target
     * @return bool
     */
    protected function isTargeted(\ReflectionClass $class, \ReflectionClass $target = null): bool
    {
        if (empty($target)) {
            return true;
        }

        if (!$target->isTrait()) {
            //Target is interface or class
            return $class->isSubclassOf($target) || $class->getName() == $target->getName();
        }

        //Checking using traits
        return in_array($target->getName(), $this->fetchTraits($class->getName()));
    }
}
