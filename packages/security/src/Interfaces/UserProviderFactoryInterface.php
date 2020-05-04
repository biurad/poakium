<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\Interfaces;

use Nette\DI\ContainerBuilder;

/**
 * UserProviderFactoryInterface is the interface for all user provider factories.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
interface UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config);

    public function getKey();

    public function addConfiguration(\Nette\Schema\Expect $builder);
}
