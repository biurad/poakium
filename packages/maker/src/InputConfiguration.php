<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  Scaffolds Maker
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/scaffoldsmaker
 * @since     Version 0.1
 */

namespace BiuradPHP\Scaffold;

final class InputConfiguration
{
    private $nonInteractiveArguments = [];

    /**
     * Call in MakerInterface::configureCommand() to disable the automatic interactive
     * prompt for an argument.
     */
    public function setArgumentAsNonInteractive(string $argumentName)
    {
        $this->nonInteractiveArguments[] = $argumentName;
    }

    public function getNonInteractiveArguments(): array
    {
        return $this->nonInteractiveArguments;
    }
}
