<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Traits;

use Rade\DI\Exceptions\NotFoundServiceException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

trait InteractsWithConsole
{
    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a command.
     *
     * Available execution options:
     *  - interactive: Sets the input interactive flag
     *  - decorated: Sets the output decorated flag
     *  - verbosity: Sets the output verbosity flag
     *  - capture_stderr_separately: Make output of stdOut and stdErr separately available
     *
     * @param string $command   The command class
     * @param array  $arguments All the arguments passed when executing the command
     * @param array  $options   An array of execution options
     */
    public function runCommand(Command|string $command, array $arguments = [], array $options = []): CommandTester
    {
        $command = $this->getCommand($command);
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments, $options);

        return $commandTester;
    }

    /**
     * Run command with debug verbosity level (-vv).
     *
     * @see runCommand() method
     */
    public function runCommandDebug(Command|string $command, array $arguments = []): CommandTester
    {
        return $this->runCommand(
            $command,
            $arguments,
            ['verbosity' => OutputInterface::VERBOSITY_DEBUG]
        );
    }

    /**
     * Run command with very verbose verbosity level (-vvv).
     *
     * @see runCommand() method
     */
    public function runCommandVeryVerbose(Command|string $command, array $arguments = []): CommandTester
    {
        return $this->runCommand(
            $command,
            $arguments,
            ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]
        );
    }

    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a command and return it's class object.
     *
     * @throws CommandNotFoundException if command not found
     */
    public function getCommand(Command|string $command): Command
    {
        if (\is_string($command)) {
            $console = $this->getConsole();

            if ($console->has($command)) {
                $command = $console->get($command);
            } else {
                try {
                    $command = $this->makeApp()->get($command);
                    $command->setApplication($console);
                } catch (NotFoundServiceException $e) {
                    throw new CommandNotFoundException(\sprintf('It looks like your command %s isn\'t valid', $command), [], 0, $e);
                }
            }
        } elseif (!$command->getApplication()) {
            $command->setApplication($this->getConsole());
        }

        return $command;
    }

    /**
     * Setup up the Router instance.
     */
    protected function getConsole(): Application
    {
        return $this->makeApp()->get(Application::class);
    }
}
