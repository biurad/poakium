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

namespace BiuradPHP\Loader\Annotations;

use BiuradPHP\Loader\Interfaces\ClassInterface;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Locate all available annotations for methods, classes and properties across all the codebase.
 */
final class AnnotationLoader
{
    /** @var ClassInterface */
    private $classLocator;

    /** @var AnnotationReader */
    private $reader;

    /** @var array */
    private $targets = [];

    /**
     * AnnotationLocator constructor.
     *
     * @param ClassInterface      $classLocator
     * @param AnnotationReader|null $reader
     *
     * @throws AnnotationException
     */
    public function __construct(ClassInterface $classLocator, AnnotationReader $reader = null)
    {
        $this->classLocator = $classLocator;
        $this->reader = $reader ?? new DoctrineAnnotationReader();
    }

    /**
     * Limit locator to only specific class types.
     *
     * @param array $targets
     * @return AnnotationLocator
     */
    public function withTargets(array $targets): self
    {
        $locator = clone $this;
        $locator->targets = $targets;

        return $locator;
    }

    /**
     * Find all classes with given annotation.
     *
     * @param string $annotation
     * @return iterable|array<ReflectionClass, object>
     */
    public function findClasses(string $annotation): ?iterable
    {
        foreach ($this->getTargets() as $target) {
            foreach ($this->reader->getClassAnnotations($target) as $found) {
                if ($found instanceof $annotation) {
                    yield [$target, $found];
                }
            }
        }

        return null;
    }

    /**
     * Find all methods with given annotation.
     *
     * @param string $annotation
     * @return iterable|array<ReflectionMethod, object>
     */
    public function findMethods(string $annotation): ?iterable
    {
        foreach ($this->getTargets() as $target) {
            foreach ($target->getMethods() as $method) {
                foreach ($this->reader->getMethodAnnotations($method) as $found) {
                    if ($found instanceof $annotation) {
                        yield [$method, $found];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find all properties with given annotation.
     *
     * @param string $annotation
     * @return iterable|array<ReflectionProperty, object>
     */
    public function findProperties(string $annotation): ?iterable
    {
        foreach ($this->getTargets() as $target) {
            foreach ($target->getProperties() as $property) {
                foreach ($this->reader->getPropertyAnnotations($property) as $found) {
                    if ($found instanceof $annotation) {
                        yield [$property, $found];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return iterable|ReflectionClass[]
     */
    private function getTargets(): iterable
    {
        if ($this->targets === []) {
            yield from $this->classLocator->getClasses();

            return;
        }

        foreach ($this->targets as $target) {
            yield from $this->classLocator->getClasses($target);
        }
    }
}
