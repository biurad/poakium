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

namespace BiuradPHP\Scaffold;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractDeclaration implements Interfaces\MakerDeclareInterface
{
    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, SymfonyStyle $io, Command $command): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function writeMessage(): ?array
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    abstract public static function getCommandName(): string;

    /**
     * {@inheritdoc}
     */
    abstract public function configureCommand(Command $command, InputConfiguration $inputConf): void;

    /**
     * {@inheritdoc}
     */
    abstract public function generate(InputInterface $input, $element, Generator $generator): void;

    /**
     * @param array  $dependencies
     * @param string $message
     *
     * @return string
     */
    protected function addDependencies(array $dependencies, string $message = null): string
    {
        $dependencyBuilder = new DependencyBuilder();

        foreach ($dependencies as $class => $name) {
            $dependencyBuilder->addClassDependency($class, $name);
        }

        return $dependencyBuilder->getMissingPackagesMessage($this->getCommandName(), $message);
    }
}
