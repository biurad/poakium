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

namespace BiuradPHP\Cache\Bridges;

use Nette, BiuradPHP;

class CacheExtension extends BiuradPHP\DependencyInjection\CompilerExtension
{
    /** @var string */
	private $tempDir;


	public function __construct(string $tempDir)
	{
		$this->tempDir = $tempDir;
    }

    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        return Nette\Schema\Expect::structure([
            'enabled' => Nette\Schema\Expect::bool(),
            'driver' => Nette\Schema\Expect::string()->default(null),
            'pools' => Nette\Schema\Expect::arrayOf('string|array'),
		])->otherItems('mixed');
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
	{
        $builder = $this->getContainerBuilder();

		$builder->addDefinition('cache')
            ->setFactory(BiuradPHP\Cache\Bridges\CacheResolver::class)
            ->setArguments([$this->config, $this->tempDir]);

        $builder->addDefinition('cache.doctrine')
            ->setFactory('@BiuradPHP\Cache\Bridges\CacheResolver::getPoolAdapter')
            ->setAutowired(false);
    }
}
