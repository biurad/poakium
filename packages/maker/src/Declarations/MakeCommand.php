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

namespace BiuradPHP\Scaffold\Declarations;

use BiuradPHP\Scaffold\AbstractDeclaration;
use BiuradPHP\Scaffold\ClassNameDetails;
use BiuradPHP\Scaffold\ConfigInjector;
use BiuradPHP\Scaffold\DependencyBuilder;
use BiuradPHP\Scaffold\Exceptions\RuntimeCommandException;
use BiuradPHP\Scaffold\Generator;
use BiuradPHP\Scaffold\HelperUtil;
use BiuradPHP\Scaffold\InputConfiguration;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\Property;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeCommand extends AbstractDeclaration implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'command';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->setDescription('Creates a new console command class')
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('Choose a command name (e.g. <fg=yellow>app:%s</>)', HelperUtil::asCommand(HelperUtil::getRandomTerm())))
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command generates a new command:

<info>php %command.full_name% app:something</info>

If the argument is missing, the command will ask for the command name interactively.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(InputInterface $input, $element, Generator $generator): void
    {
        $commandName = trim($input->getArgument('name'));
        $commandNameHasAppPrefix = 0 === strpos($commandName, 'app:');

        $commandClassDetails = $generator->createClassNameDetails(
            $commandNameHasAppPrefix ? substr($commandName, 4) : $commandName,
            is_string($element) ? $generator->getNamespace($element) : 'Commands\\',
            is_string($element) ? $generator->getSuffix($element) : 'Command',
            sprintf('The "%s" command name is not valid because it would be implemented by "%s" class, which is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores).', $commandName, HelperUtil::asClassName($commandName, 'Command'))
        );

        if (!file_exists($path = BR_PATH. 'config/packages/_terminal.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/_terminal.yaml" does not exist. This command needs that file to accurately build the console commands.');
        }

        $injector = new ConfigInjector($path, 1);
        if (null !== $this->logger) {
            $injector->setLogger($this->logger);
        }

        $newData = $injector->getData();
        $newData['terminal']['commands'][] = ['class' => $commandClassDetails->getFullName(), 'tags' => ['console.command' => $commandName]];
        $injector->setData($newData);

        $generator->generateClass($this->declareStructure($commandClassDetails, $commandName), null);

        $generator->dumpFile($path, $injector->getContents());
        $generator->writeChanges();
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Command::class,
            'symfony/console'
        );
    }

    /**
     * Write a Command Delcaration
     *
     * @param ClassNameDetails $declare
     * @param string $commandName
     *
     * @return ClassNameDetails
     */
    private function declareStructure(ClassNameDetails $declare, string $commandName): ClassNameDetails
    {
        $parameters = [
            (new Parameter('input'))->setType(InputInterface::class),
            (new Parameter('output'))->setType(OutputInterface::class),
        ];

        $declare
            ->addProperty(
                (new Property('defaultName'))
                    ->setStatic(true)
                    ->setValue($commandName)
                    ->addComment('The default command id, incase the command id wasn\'t set')
                    ->addComment('@var string')
                ->setPublic()
            )
            ->addProperty(
                (new Property('io'))
                    ->addComment('@var SymfonyStyle')
                ->setPrivate()
            )
            ->addMethod(
                (new Method('configure'))
                    ->addBody('$this')
                        ->addBody("\t->setDescription('Add a short description for your command')")
                        ->addBody("\t->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')")
                        ->addBody("\t->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')")
                    ->addBody(';')
                    ->setComment('{@inheritdoc}')
                    ->setReturnType('void')
                ->setProtected()
            )
            ->addMethod(
                (new Method('initialize'))
                    ->addBody('// SymfonyStyle is an optional feature that Symfony provides so you can')
                    ->addBody('// apply a consistent look to the commands of your application.')
                    ->addBody('// See https://symfony.com/doc/current/console/style.html')
                    ->addBody('$this->io = new SymfonyStyle($input, $output);')
                    ->addComment("{@inheritdoc}\n")
                    ->addComment('This optional method is the first one executed for a command after configure()')
                    ->addComment('and is useful to initialize properties based on the input arguments and options.')
                    ->setReturnType('void')
                    ->setParameters($parameters)
                    ->setReturnType('void')
                ->setProtected()
            )
            ->addMethod(
                (new Method('interact'))
                    ->setBody('//TODO: Should the command be interactive...')
                    ->setComment('{@inheritdoc}')
                    ->setReturnType('void')
                    ->setParameters($parameters)
                    ->setReturnType('void')
                ->setProtected()
            )
            ->addMethod(
                (new Method('execute'))
                    ->addBody('if ($arg1 = $input->getArgument(\'arg1\')) {')
                        ->addBody("\t".'$this->io->note(sprintf(\'You passed an argument: %s\', $arg1));'."\n}\n")
                        ->addBody('if ($input->getOption(\'option1\')) {' . "\n\t// ...\n}\n")
                        ->addBody('$this->io->success(\'You have a new command! Now make it your own! Pass --help to see your options.\');')
                    ->addBody("\nreturn 0;")
                    ->setComment('{@inheritdoc}')
                    ->setReturnType('void')
                    ->setParameters($parameters)
                    ->setReturnType('int')
                ->setProtected()
            )
            ->setUses([
                Command::class          => null,
                InputOption::class      => null,
                InputArgument::class    => null,
                InputInterface::class   => null,
                OutputInterface::class  => null,
                SymfonyStyle::class     => null,
            ])
            ->setExtended(Command::class)
        ;

        return $declare;
    }
}
