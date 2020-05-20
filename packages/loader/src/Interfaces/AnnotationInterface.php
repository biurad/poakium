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

namespace BiuradPHP\Loader\Interfaces;

use BiuradPHP\Loader\Annotations\AnnotationLoader;

/**
 * This is a fluent implementation of AnnotationLoader.
 *
 * If you wish to use a robust system where all annotations
 * organised in an array are loaded in a flexible way,
 * without having to register individually.
 */
interface AnnotationInterface
{
    /**
     * Add a new annotation to stack.
     *
     * (Example of Implementation):
     * ```php
     * $annotations = [...]; // organised annotations.
     * $loader = new BiuradPHP\Loader\Annotations\AnnotationLoader($classLocator);
     *
     * foreach ($annotations as $annotation) {
     *      if (!is_object($annotation)) {
     *          $annotation = new $annotation();
     *      }
     *
     *      $annotation->register($loader);
     * }
     * ```
     *
     * @param AnnotationLoader $loader
     */
    public function register(AnnotationLoader $loader): void;
}
