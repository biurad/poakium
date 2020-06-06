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

namespace BiuradPHP\Loader\Files {

use BiuradPHP\Loader\Locators\FileLocator;

if (false) {
		/** @deprecated use BiuradPHP\Loader\Files\FileLoader */
		class FileLoader
		{
		}
	} elseif (!class_exists(FileLoader::class)) {
		class_alias(FileLocator::class, FileLoader::class);
	}
}

namespace BiuradPHP\Loader\Annotations {

use BiuradPHP\Loader\Locators\AnnotationLocator;

if (false) {
		/** @deprecated use BiuradPHP\Loader\Files\FileLoader */
		class AnnotationLoader
		{
		}
	} elseif (!class_exists(AnnotationLoader::class)) {
		class_alias(AnnotationLocator::class, AnnotationLoader::class);
	}
}
