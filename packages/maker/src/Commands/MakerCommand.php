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

namespace BiuradPHP\Scaffold\Commands;

use BiuradPHP\Scaffold\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MakerCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $return  = parent::execute($input, $output);
        $message = [
            \sprintf('Next: open your new %s class and customize it!', $this->maker::getCommandName()),
            'Find the documentation at <fg=yellow>https://docs.biurad.com/doc/scaffold-maker</>',
        ];

        if (\method_exists($this->maker, 'writeMessage')) {
            $message = $this->maker->writeMessage() ? $this->maker->writeMessage() : $message;
        }

        $this->io->text($message);

        return $return;
    }
}
