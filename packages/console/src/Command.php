<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command line.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\ConsoleLite;

use Psr\Log\LogLevel;

/**
 * The Command Runner.
 *
 * The Command class contains the core functionality of ConsoleLite.
 * It is responsible for loading methods and functions from the application class,
 * running the registered commands, and generating response.
 *
 * @method \BiuradPHP\Toolbox\ConsoleLite\Formatter            getFormatter()   Gets the Fomatter class.
 * @method \BiuradPHP\Toolbox\ConsoleLite\Terminal             getTerminal()    Gets the terminal class.
 * @method \BiuradPHP\Toolbox\ConsoleLite\Concerns\Silencer    getSilencer()    Get rid of php warnings
 * @method \BiuradPHP\Toolbox\FilePHP\FileHandler              getFileHandler() Gets the FileHandler class.
 * @method \BiuradPHP\Toolbox\ConsoleLite\Colors               getColors()      Get the Color class.
 * @method \BiuradPHP\Toolbox\ConsoleLite\Terminal             generate_clite() Generate the config file.
 * @method \BiuradPHP\Toolbox\ConsoleLite\Concerns\Highlighter getHighLighter() Get the Highlighter class.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
abstract class Command
{
    protected $app;

    protected $aliases;

    protected $signature;

    protected $description;

    public function getSignature()
    {
        return $this->signature;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function defineApp(Application $app)
    {
        if (!$this->app) {
            $this->app = $app;
        }
    }

    public function getApp()
    {
        $this->app;
    }

    /**
     * Logs with an arbitrary level.
     * PSR-3 compatible loglevels.
     *
     * @param mixed       $level
     * @param string      $message
     * @param string|null $type
     */
    public function addLog(string $message, ?string $type = null)
    {
        switch ($type) {
            case LogLevel::WARNING:
                $this->warning($message, 'writeln');
                break;
            case LogLevel::NOTICE:
            case LogLevel::INFO:
                $this->notice($message, 'writeln');
                break;
            case LogLevel::EMERGENCY:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
            case LogLevel::ALERT:
                $this->error($message, 'writeln');
                break;
            case 'success':
                $this->writeln($message, 'green');
                break;
            case 'debug':
                $this->writeDebug($message, 'writeln');
                break;
            default:
                $this->writeln($message, 'none');
                break;
        }
    }

    /**
     * Write a hidden debugger message on console.
     *
     * @param string|array $message
     * @param array|string $context
     */
    public function debug($message, $context = [])
    {
        $this->writeDebug($message, $context ?: false);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public function error($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, 'light_red');
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_light_red', 'white']);
        }

        return $this->write($message, 'light_red');
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, 'light_yellow');
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_light_yellow', 'red']);
        }

        return $this->write($message, 'light_yellow');
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public function notice($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, 'yellow');
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_yellow', 'light_red']);
        }

        return $this->write($message, 'yellow');
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    public function emergency($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, 'red');
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_red', 'white']);
        }

        return $this->write($message, 'red');
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    public function alert($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, 'cyan');
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_dark_gray', 'cyan']);
        }

        return $this->write($message, 'cyan');
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    public function critical($message, $context = [])
    {
        if ($context === 'writeln') {
            return $this->writeln($message, ['light_red', 'bold']);
        }
        if ($context === 'block') {
            return $this->block($message, ['bg_light_red', 'white', 'bold']);
        }

        return $this->write($message, ['light_red', 'bold']);
    }

    public function __call($method, $args)
    {
        if ($this->app and method_exists($this->app, $method)) {
            return call_user_func_array([$this->app, $method], $args);
        } else {
            $class = get_class($this);

            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s', $class, $method));
        }
    }
}
