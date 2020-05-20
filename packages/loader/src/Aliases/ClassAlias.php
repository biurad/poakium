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

namespace BiuradPHP\Loader\Aliases;

use BiuradPHP\Loader\Interfaces\AliasTypeInterface;

class ClassAlias implements AliasTypeInterface
{
    /**
     * @var array
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct(string $alias, string $class)
    {
        $this->value = [$alias => $class];
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): array
    {
        return $this->value;
    }
}
