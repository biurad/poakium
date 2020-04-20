<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  CacheManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/cachemanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Cache;

use InvalidArgumentException;

use function ob_start;
use function ob_get_flush;

/**
 * Output caching helper.
 */
class OutputHelper
{
	/** @var Caching|null */
	private $cache;

	/** @var string */
	private $key;


	public function __construct(Caching $cache, $key)
	{
		$this->cache = $cache;
		$this->key = $key;
		ob_start();
	}


	/**
	 * Stops and saves the cache.
	 */
	public function end(array $dependencies = []): void
	{
		if ($this->cache === null) {
			throw new InvalidArgumentException('Output cache has already been saved.');
		}
        $this->cache->save($this->key, ob_get_flush(), $dependencies);

		$this->cache = null;
	}
}
