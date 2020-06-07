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
     * @param array $dependencies
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

        return $dependencyBuilder->getMissingPackagesMessage(
            $this->getCommandName(),
            $message
        );
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
}
