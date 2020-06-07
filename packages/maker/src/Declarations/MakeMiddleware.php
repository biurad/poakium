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
use BiuradPHP\Scaffold\DependencyBuilder;
use BiuradPHP\Scaffold\Generator;
use BiuradPHP\Scaffold\InputConfiguration;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class MakeMiddleware extends AbstractDeclaration
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'middleware';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->setDescription('Creates a new middleware class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your middleware (e.g. <fg=yellow>Redirect</>)')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command generates a new middleware class.

<info>php %command.full_name% redirect</info>

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
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            is_string($element) ? $generator->getNamespace($element) : 'Middlewares\\',
            is_string($element) ? $generator->getSuffix($element) : 'Middleware'
        );

        $testClassNameDetails
            ->addMethod(
                (new Method('process'))
                    ->setComment('{@inheritdoc}')
                    ->setParameters([
                        (new Parameter('request'))->setType(ServerRequestInterface::class),
                        (new Parameter('handler'))->setType(RequestHandlerInterface::class),
                    ])
                    ->setBody('return $handler->handle($request);')
                    ->setReturnType(ResponseInterface::class)
                ->setPublic()
            )
            ->setUses([
                MiddlewareInterface::class     => null,
                RequestHandlerInterface::class => null,
                ResponseInterface::class       => 'Response',
                ServerRequestInterface::class  => 'Request'
            ])
            ->setImplements([MiddlewareInterface::class])
        ;

        $generator->generateClass($testClassNameDetails, null);
        $generator->writeChanges();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            RequestHandlerInterface::class,
            'psr/http-server-handler'
        );

        $dependencies->addClassDependency(
            MiddlewareInterface::class,
            'psr/http-server-middleware'
        );
    }
}
