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

namespace BiuradPHP\Scaffold\Interfaces;

use Symfony\Component\Console\Application;

/**
 * Implement this interface if your Maker needs access to the Application.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
interface ApplicationAwareInterface
{
    public function setApplication(Application $application);
}
