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

use BiuradPHP\Events\Annotation\Listener;
use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use BiuradPHP\MVC\KernelEvents;
use BiuradPHP\Scaffold\AbstractDeclaration;
use BiuradPHP\Scaffold\ConfigInjector;
use BiuradPHP\Scaffold\DependencyBuilder;
use BiuradPHP\Scaffold\Exceptions\RuntimeCommandException;
use BiuradPHP\Scaffold\Generator;
use BiuradPHP\Scaffold\HelperUtil;
use BiuradPHP\Scaffold\InputConfiguration;
use BiuradPHP\Scaffold\Validator;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpLiteral;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class MakeSubscriber extends AbstractDeclaration
{
    private $eventDispatcher;
    private $logger;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'subscriber';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->setDescription('Creates a new event subscriber class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event subscriber (e.g. <fg=yellow>Exception</>)')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to subscribe to?')
            ->addOption('annotation', 'a', InputOption::VALUE_NONE, 'Will use annotation config instean of file')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command generates a new event subscriber class.

<info>php %command.full_name% Exception</info>

If the argument is missing, the command will ask for the class name interactively.
EOF
            )
        ;

        $inputConf->setArgumentAsNonInteractive('event');
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, SymfonyStyle $io, Command $command): void
    {
        if (!$input->getArgument('event')) {
            $events = $this->getAllActiveEvents();

            $io->writeln(' <fg=green>Suggested Events:</>');
            $io->listing($this->listActiveEvents($events));
            $question = new Question(sprintf(' <fg=green>%s</>', $command->getDefinition()->getArgument('event')->getDescription()));
            $question->setAutocompleterValues($events);
            $question->setValidator([Validator::class, 'notBlank']);
            $event = $io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate(InputInterface $input, $element, Generator $generator): void
    {
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            is_string($element) ? $generator->getNamespace($element) : 'EventSubscribers\\',
            is_string($element) ? $generator->getSuffix($element) : 'Subscriber'
        );

        $event = $input->getArgument('event');
        $eventFullClassName = $this->getEventClassName($event);
        $eventClassName = $eventFullClassName ? HelperUtil::getShortClassName($eventFullClassName) : null;
        $method = class_exists($event) ? HelperUtil::asEventMethod($eventClassName) : HelperUtil::asEventMethod($event);
        $annotation = false !== $input->getOption('annotation');

        foreach ((new \ReflectionClass(KernelEvents::class))->getConstants() as $constant => $class) {
            if ($event === $class) {
                $eventClassName = sprintf('%s::%s', HelperUtil::getShortClassName(KernelEvents::class), $constant);
                $testClassNameDetails->setUses([KernelEvents::class => null]);
                break;
            }
        }

        $event = class_exists($event) ? new PhpLiteral($eventClassName) : sprintf('\'%s\'', $event);
        $eventArgs = null !== $eventClassName ? [(new Parameter('event'))->setType($eventFullClassName)] : [];

        // Add the event class to use statements...
        if (null !== $eventFullClassName) {
            $testClassNameDetails->setUses([$eventFullClassName => null]);
        }

        $testClassNameDetails
            ->addMethod(
                (new Method('getSubscribedEvents'))
                    ->setStatic(true)
                    ->setComment('{@inheritdoc}')
                    ->addBody('return [')
                        ->addBody("\t".sprintf('%s => \'%s\',', $event, $method))
                    ->addBody('];')
                ->setPublic()
            )
            ->addMethod(
                (new Method($method))
                    ->setBody('// ...')
                    ->setParameters($eventArgs)
                ->setPublic()
            )
            ->setUses([EventSubscriberInterface::class => null])
            ->setImplements([EventSubscriberInterface::class])
        ;

        // Add to configuration file, by default...
        if (false !== $annotation) {
            $testClassNameDetails
                ->setComments([HelperUtil::buildAnnotationLine('@Listener', [])])
                ->setUses([Listener::class => null]);
        } else {
            if (!file_exists($path = BR_PATH. 'config/packages/_dispatcher.yaml')) {
                throw new RuntimeCommandException('The file "config/packages/_dispatcher.yaml" does not exist. This command needs that file to accurately build the event subscriber.');
            }
            
            $injector = new ConfigInjector($path);
            if (null !== $this->logger) {
                $injector->setLogger($this->logger);
            }

            $newData = $injector->getData();
            $newData['events']['subscribers'][] = $testClassNameDetails->getFullName();
            $injector->setData($newData);

            $generator->dumpFile($path, $injector->getContents());
        }

        $generator->generateClass($testClassNameDetails, null);
        $generator->writeChanges();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            EventSubscriberInterface::class,
            'biurad/biurad-events-bus'
        );
    }

    /**
     * Returns all known event names in the system.
     */
    private function getAllActiveEvents(): array
    {
        $activeEvents = [];

        // Check if these listeners are part of the new events.
        foreach ($this->eventDispatcher->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                $activeEvents[$listener->getEvent()] = $this->getEventClassName($listener->getEvent());
            }
        }

        asort($activeEvents);

        return $activeEvents;
    }

    /**
     * Attempts to get the event class for a given event.
     */
    private function getEventClassName(string $event)
    {
        // if the event is already a class name, use it
        if (class_exists($event)) {
            return $event;
        }

        $listeners = $this->eventDispatcher->getListener($event);
        if (empty($listeners)) {
            return null;
        }

        foreach ($listeners as $listener) {
            $listener = $listener->getListener();
            if (!is_array($listener) || 2 !== count($listener)) {
                continue;
            }

            $reflectionMethod = new \ReflectionMethod($listener[0], $listener[1]);
            $args = $reflectionMethod->getParameters();
            if (!$args) {
                continue;
            }

            if (null !== $type = $args[0]->getType()) {
                $type = $type instanceof \ReflectionNamedType ? $type->getName() : $type->__toString();

                // ignore an "object" type-hint
                if ('object' === $type) {
                    continue;
                }

                return $type;
            }
        }

        return null;
    }

    private function listActiveEvents(array $events)
    {
        foreach ($events as $key => $event) {
            $events[$key] = $event;
        }

        return $events;
    }
}
