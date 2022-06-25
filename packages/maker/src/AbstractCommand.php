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

use BiuradPHP\DependencyInjection\Interfaces\FactoryInterface;
use BiuradPHP\Scaffold\Exceptions\RuntimeCommandException;
use BiuradPHP\Scaffold\Interfaces\ApplicationAwareInterface;
use BiuradPHP\Scaffold\Interfaces\MakerDeclareInterface;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    /**
     * Element to be managed.
     *
     * @var string
     */
    protected $element;

    /** @var SymfonyStyle */
    protected $io;

    protected $maker;

    protected $files;

    protected $config;

    protected $factory;

    protected $generator;

    protected $inputConfig;

    protected $checkDependencies = true;

    /**
     * @param Config\MakerConfig           $config
     * @param FactoryInterface             $container
     * @param FileManager                  $fileManager
     * @param Generator                    $generator
     * @param MakerDeclareInterface|string $element
     */
    public function __construct(
        Config\MakerConfig $config,
        FactoryInterface $container,
        FileManager $fileManager = null,
        Generator $generator = null,
        $element = null
    ) {
        $this->config  = $config;
        $this->factory = $container;
        $this->maker   = $element;

        $this->inputConfig = new InputConfiguration();
        $this->files       = $fileManager ?? new FileManager($config->baseDirectory(), $config->baseNamespace());
        $this->generator   = $generator ?? new Generator($this->config, $this->files);

        if (!$this->maker instanceof MakerDeclareInterface) {
            $this->maker = $this->declarationClass($element ?? $this->element);
        }

        parent::__construct(\is_string($element) ? \sprintf('make:%s', $this->maker::getCommandName()) : null);
    }

    public function setApplication(Application $application = null): void
    {
        parent::setApplication($application);

        if ($this->maker instanceof ApplicationAwareInterface) {
            if (null === $application) {
                throw new RuntimeException('Application cannot be null.');
            }

            $this->maker->setApplication($application);
        }
    }

    /**
     * @internal Used for testing commands
     */
    public function setCheckDependencies(bool $checkDeps): void
    {
        $this->checkDependencies = $checkDeps;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->maker->configureCommand($this, $this->inputConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->files->setIO($this->io);

        if ($this->checkDependencies) {
            $dependencies = new DependencyBuilder();
            $this->maker->configureDependencies($dependencies, $input);

            if (!$dependencies->isPhpVersionSatisfied()) {
                throw new RuntimeCommandException('The make:entity command requires that you use PHP 7.1 or higher.');
            }

            if ($missingPackagesMessage = $dependencies->getMissingPackagesMessage($this->getName())) {
                throw new RuntimeCommandException($missingPackagesMessage);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->getDefinition()->getArguments() as $argument) {
            if ($input->getArgument($argument->getName())) {
                continue;
            }

            if (\in_array($argument->getName(), $this->inputConfig->getNonInteractiveArguments(), true)) {
                continue;
            }

            $value = $this->io->ask(
                $argument->getDescription(),
                $argument->getDefault(),
                [Validator::class, 'notBlank']
            );
            $input->setArgument($argument->getName(), $value);
        }

        $this->maker->interact($input, $this->io, $this);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->maker->generate($input, $this->element ?? $this->maker, $this->generator);

        // sanity check for custom makers
        if ($this->generator->hasPendingOperations()) {
            throw new LogicException('Make sure to call the writeChanges() method on the generator.');
        }

        return 0;
    }

    /**
     * @param string $element
     *
     * @return MakerDeclareInterface
     */
    protected function declarationClass(string $element): MakerDeclareInterface
    {
        $this->element = $element;

        return $this->factory->createInstance(
            $this->config->declarationClass($element),
            $this->config->declarationOptions($element)
        );
    }
}
