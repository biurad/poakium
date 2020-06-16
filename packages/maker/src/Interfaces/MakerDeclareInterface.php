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

use BiuradPHP\Scaffold\DependencyBuilder;
use BiuradPHP\Scaffold\Generator;
use BiuradPHP\Scaffold\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

interface MakerDeclareInterface
{
    /**
     * Return the command name for your maker (e.g. report).
     * This will print a result command of (e.g. make:report).
     *
     * @return string
     */
    public static function getCommandName(): string;

    /**
     * Configure the command: set description, input arguments, options, etc.
     *
     * By default, all arguments will be asked interactively. If you want
     * to avoid that, use the $inputConfig->setArgumentAsNonInteractive() method.
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void;

    /**
     * Configure any library dependencies that your maker requires.
     */
    public function configureDependencies(DependencyBuilder $dependencies): void;

    /**
     * If necessary, you can use this method to interactively ask the user for input.
     */
    public function interact(InputInterface $input, SymfonyStyle $io, Command $command): void;

    /**
     * Called after normal code generation: allows you to do anything.
     *
     * @param InputInterface               $input
     * @param MakerDeclareInterface|string $element
     * @param Generator                    $generator
     */
    public function generate(InputInterface $input, $element, Generator $generator): void;
}
