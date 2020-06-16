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

namespace BiuradPHP\Scaffold\Declarations;

use App\Tests\TestCase;
use BiuradPHP\Scaffold\AbstractDeclaration;
use BiuradPHP\Scaffold\DependencyBuilder;
use BiuradPHP\Scaffold\Generator;
use BiuradPHP\Scaffold\InputConfiguration;
use InvalidArgumentException;
use Nette\PhpGenerator\Method;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class MakeUnitTest extends AbstractDeclaration
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'unit-test';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->setDescription('Creates a new phpunit test class')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'The name of the unit test class (e.g. <fg=yellow>Util</>)'
            )
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'The type of the testing (e.g. <fg=yellow>unit, feature, or console</>)',
                'unit'
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command generates a new unit test class.

<info>php %command.full_name% UtilTest</info>

If the argument is missing, the command will ask for the class name interactively.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(InputInterface $input, $element, Generator $generator): void
    {
        if (!\in_array($type = $input->getArgument('type'), ['unit', 'feature', 'console'], true)) {
            throw new InvalidArgumentException(
                \sprintf('Expected any of [%s], %s given', \implode(', ', ['unit', 'feature', 'console'], $type))
            );
        }

        $namespace = \is_string($element)
            ? $generator->getNamespace(\rtrim($element, '\\') . '\\' . \ucfirst($type))
            : 'Tests\\' . \ucfirst($type);

        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            $namespace,
            \is_string($element) ? $generator->getSuffix($element) : 'Test'
        );

        $testClassNameDetails
            ->addMethod(
                (new Method('testSomething'))
                    ->setBody('$this->assertTrue(true);')
                ->setPublic()
            )
            ->setUses([TestCase::class => null])
            ->setExtended(TestCase::class)
        ;

        $generator->generateClass($testClassNameDetails, null);
        $generator->writeChanges();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(PHPUnitTestCase::class, 'phpunit/phpunit', true, true);
    }
}
