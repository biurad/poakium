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

namespace BiuradPHP\Scaffold\EventListeners;

use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use BiuradPHP\MVC\KernelEvents;
use BiuradPHP\Scaffold\Exceptions\RuntimeCommandException;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prints certain exceptions in a pretty way and silences normal exception handling.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class ConsoleErrorSubscriber implements EventSubscriberInterface
{
    private $setExitCode = false;

    public function onConsoleError(ConsoleErrorEvent $event)
    {
        if (!$event->getError() instanceof RuntimeCommandException) {
            return;
        }

        // prevent any visual logging from appearing
        $event->stopPropagation();
        // prevent the exception from actually being thrown
        $event->setExitCode(0);
        $this->setExitCode = true;

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->error($event->getError()->getMessage());
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$this->setExitCode) {
            return;
        }

        // finally set a non-zero exit code
        $event->setExitCode(1);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONSOLE_ERROR => 'onConsoleError',
            KernelEvents::CONSOLE_TERMINATE => 'onConsoleTerminate',
        ];
    }
}
