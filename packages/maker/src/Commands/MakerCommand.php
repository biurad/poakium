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

namespace BiuradPHP\Scaffold\Commands;

use BiuradPHP\Scaffold\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MakerCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $return = parent::execute($input, $output);
        $message = [
            sprintf('Next: open your new %s class and customize it!', $this->maker::getCommandName()),
            'Find the documentation at <fg=yellow>https://docs.biurad.com/doc/scaffold-maker</>',
        ];
        
        if (method_exists($this->maker, 'writeMessage')) {
            $message = $this->maker->writeMessage() ? $this->maker->writeMessage(): $message;
        }

        $this->io->text($message);

        return $return;
    }
}
