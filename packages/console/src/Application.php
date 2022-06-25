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

use BiuradPHP\Toolbox\ConsoleLite\Commands\CommandAbout;
use BiuradPHP\Toolbox\ConsoleLite\Commands\CommandList;
use BiuradPHP\Toolbox\ConsoleLite\Commands\CommandPhar;
use BiuradPHP\Toolbox\ConsoleLite\Commands\CommandStub;
use BiuradPHP\Toolbox\ConsoleLite\Concerns\Highlighter;
use BiuradPHP\Toolbox\ConsoleLite\Exceptions\ConsoleLiteException;
use BiuradPHP\Toolbox\ConsoleLite\Exceptions\DeprecatedException;
use BiuradPHP\Toolbox\ConsoleLite\Exceptions\ExpectedException;
use InvalidArgumentException;

/**
 * ConsoleLite Application.
 *
 * This is a toolbox to interact with CLI (command-line interface).
 * This application is dynamically exceptional, having some useful
 * features.
 *
 * After the release of version 1.0 to 1.4.2, ConsoleLite, had
 * issues with colors on cli, few functions not working,
 * files and directories reading and writing issues and
 * unstable performance.
 *
 * This new version thus 1.5.0 has battled tough testing, hence we
 * have a newly rebuilt ConsoleLite.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class Application extends Terminal
{
    use Concerns\Input;

    protected static $stty;
    protected static $shell;

    const CONSOLELITE_VERSION = '1.5.0';
    private $tokens;

    public $arguments = [];
    public $commands = [];
    public $options = [];
    public $optionsAlias = [];
    public $defaultCommand;
    public $verbose;
    public $title = null;
    public $version;

    protected $filename;
    protected $command;
    protected $content;
    protected $resolvedOptions = [];

    /** @var Formatter */
    protected $formatter;
    /** @var ConsoleLiteException */
    protected $errorhandle;
    /** @var Colors */
    protected $colors;
    /** @var Terminal */
    protected $terminal;
    /** @var Highlighter */
    protected $highlighter;

    /**
     * Create a new ConsoleLite console application.
     *
     * @param string $title   define your app title
     * @param string $version define your app version
     */
    public function __construct(string $title = 'ConsoleLite Application - Version', $version = self::CONSOLELITE_VERSION)
    {
        // enable the terminal
        parent::__construct();

        $argv = @$GLOBALS['argv'];
        $this->tokens = $argv;

        $this->defaultCommand = 'list';

        $this->colors = new Colors();
        $this->terminal = new Terminal();
        $this->formatter = new Formatter($this->getColors());
        $this->highlighter = new Highlighter($this->getColors());

        $this->newLine();
        $this->write($this->title = $title, 'none');
        $this->write('  ');
        $this->writeln($this->version = $version, 'green');
        $this->newLine();

        // error handlers
        $this->errorhandle = new ConsoleLiteException();
        set_exception_handler([$this, 'handleError']);
        $this->getSilencer()->call('error_reporting', 0);

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set($this->getSilencer()->call('date_default_timezone_get'));
        }

        list(
            $this->filename,
            $this->command,
            $this->arguments,
            $this->options,
            $this->optionsAlias
        ) = $this->_parseArgv($argv);

        foreach ($this->defaultCommands() as $default) {
            $this->register($default);
        }

        // Deprecated command 'welcome', was removed.
    }

    /**
     * Gets the name of the application.
     *
     * @return string The application name
     */
    public function getName()
    {
        return $this->title;
    }

    /**
     * Sets the application name.
     *
     * @param string $name The application name
     */
    public function setName($name)
    {
        $this->title = $name;
    }

    /**
     * Get the value of defaultCommand.
     */
    public function getDefaultCommand()
    {
        return $this->defaultCommand;
    }

    /**
     * Set the value of defaultCommand.
     *
     * @return self
     */
    public function setDefaultCommand($defaultCommand)
    {
        $this->defaultCommand = $defaultCommand;

        return $this;
    }

    /**
     * Get the application of version.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the application of version.
     *
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Register command.
     *
     * @param Command $command
     */
    public function register(Command $command)
    {
        try {
            list($commandName, $args, $options) = $this->_parseCommand($command->getSignature());

            if (!$commandName) {
                $class = get_class($command);

                throw new InvalidArgumentException(sprintf('Command %s must have a name defined in signature', $class));
            }

            if (!method_exists($command, 'handle')) {
                $class = get_class($command);

                throw new InvalidArgumentException(sprintf('Command %s must have a method handle', $class));
            }

            $command->defineApp($this);

            $this->commands[$commandName] = [
                'handler'     => [$command, 'handle'],
                'description' => $command->getDescription(),
                'args'        => $args,
                'options'     => $options,
            ];
        } catch (ExpectedException $e) {
            $class = get_class($command);

            throw new ExpectedException(sprintf('%s could not be found', $class), $e->getCode());
        }
    }

    /**
     * Register closure command.
     *
     * @param string  $signature   command signature
     * @param string  $description command description
     * @param Closure $handler     command handler
     */
    public function command($signature, $description, \Closure $handler)
    {
        list($commandName, $args, $options) = $this->_parseCommand($signature);

        $this->commands[$commandName] = [
            'handler'     => $handler,
            'description' => $description,
            'args'        => $args,
            'options'     => $options,
        ];
    }

    /**
     * Get registered commands.
     *
     * @return array
     */
    public function getRegisteredCommands()
    {
        return $this->commands;
    }

    /**
     * Returns a registered command by name or alias.
     *
     * @param string $name The command name or alias
     *
     * @throws ExpectedException When given command name does not exist
     *
     * @return Command A Command object
     */
    public function getCommand($name)
    {
        if (!$this->hasCommand($name)) {
            throw new ExpectedException(sprintf('The command "%s" does not exist.', $name));
        }

        $command = $this->commands[$name];

        return $command;
    }

    /**
     * Returns true if the command exists, false otherwise.
     *
     * @param string $name The command name or alias
     *
     * @return bool true if the command exists, false otherwise
     */
    public function hasCommand($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get commands like given keyword.
     *
     * @param string $keyword
     *
     * @return array
     */
    public function getCommandsLike($keyword)
    {
        $regex = preg_quote($keyword);
        $commands = $this->getRegisteredCommands();
        $matchedCommands = [];
        foreach ($commands as $name => $command) {
            if ((bool) preg_match('/'.$regex.'/', $name)) {
                $matchedCommands[$name] = $command;
            }
        }

        return $matchedCommands;
    }

    /**
     * The default commands of the application.
     */
    private function defaultCommands()
    {
        return [
            new CommandList(),
            new CommandAbout(),
            new CommandPhar(),
            //new CommandStub(),
        ];
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        $bin = $this->getSilencer()->call('basename', $this->filename);

        return $bin;
    }

    /**
     * Call the terminal class.
     *
     * @return \BiuradPHP\Toolbox\ConsoleLite\Terminal
     */
    public function getTerminal()
    {
        return $this->terminal;
    }

    /**
     * Call the Highlighter class.
     *
     * @return \BiuradPHP\Toolbox\ConsoleLite\Concerns\Highlighter
     */
    public function getHighLighter()
    {
        return $this->highlighter;
    }

    /**
     * Call the silencer class.
     *
     * @return \BiuradPHP\Toolbox\ConsoleLite\Concerns\Silencer
     */
    public function getSilencer()
    {
        return parent::getSilencer();
    }

    /**
     * Call the filephp class.
     *
     * @return \BiuradPHP\Toolbox\FilePHP\FileHandler
     */
    public function getFilehandler()
    {
        return parent::getFilehandler();
    }

    /**
     * Call the color class.
     *
     * @return \BiuradPHP\Toolbox\ConsoleLite\Colors
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * Call the formatter class.
     *
     * @return \BiuradPHP\Toolbox\ConsoleLite\Formatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Get the of verbose of the output.
     */
    public function isVerbose()
    {
        return (bool) $this->verbose;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns an Argument by name or by position.
     *
     * @param string|int $name The Argument name or position
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     *
     * @return mixed
     */
    public function getArgument($name)
    {
        if (!$this->hasArgument($name)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $arguments = \is_int($name) ? array_values($this->arguments) : $this->arguments;

        return $arguments[$name];
    }

    /**
     * Returns true if an Argument object exists by name or position.
     *
     * @param string|int $name The Argument name or position
     *
     * @return bool
     */
    public function hasArgument($name)
    {
        $arguments = \is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    /**
     * Get option by given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function hasOption($key)
    {
        if (strlen($key) <= 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid option.', $key));
        }

        return isset($this->resolvedOptions[$key]) ? $this->resolvedOptions[$key] : null;
    }

    /**
     * Has Option alias.
     *
     * @param string $key
     *
     * @return bool
     */
    private function hasoptionAlias(string $key)
    {
        return isset($this->optionAlias[$key]);
    }

    /**
     * Set Option Alias.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function optionAlias($key)
    {
        $key = ltrim($key, '-');

        if (strlen($key) > 1) {
            throw new ExpectedException('Short options should be exactly one ASCII character');
        }

        if (!$this->hasoptionAlias($key)) {
            return array_key_exists($key, $this->optionsAlias);
        }

        return $this->hasoptionAlias($key);
    }

    /**
     * Get the value of the given option.
     *
     * Please note that all options are accessed by their long option names regardless of how they were
     * specified on commandline.
     *
     * Can only be used after parseOptions() has been run
     *
     * @param mixed       $option
     * @param bool|string $default what to return if the option was not set
     *
     * @return bool|string|string[]
     */
    public function getOption($option, $default = false)
    {
        if ($option === null || !$option || $option === '') {
            return;
        }

        if (isset($this->resolvedOptions[$option])) {
            return $this->resolvedOptions[$option];
        }

        return $default;
    }

    /**
     * Check whether an option has a parameter.
     *
     * @param mixed $values
     * @param bool  $onlyParams
     *
     * @return bool
     */
    public function hasParameterOption($values, $onlyParams = false)
    {
        $values = (array) $values;

        foreach ($this->tokens as $token) {
            if ($onlyParams && '--' === $token) {
                return false;
            }
            foreach ($values as $value) {
                // Options with values:
                //   For long options, test for '--option=' at beginning
                //   For short options, test for '-o' at beginning
                $leading = 0 === strpos($value, '--') ? $value.'=' : $value;
                if ($token === $value || '' !== $leading && 0 === strpos($token, $leading)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the parameter for an option.
     *
     * @param mixed $values
     * @param bool  $default
     * @param bool  $onlyParams
     */
    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
        $values = (array) $values;
        $tokens = $this->tokens;

        while (0 < \count($tokens)) {
            $token = array_shift($tokens);
            if ($onlyParams && '--' === $token) {
                return $default;
            }

            foreach ($values as $value) {
                if ($token === $value) {
                    return array_shift($tokens);
                }
                // Options with values:
                //   For long options, test for '--option=' at beginning
                //   For short options, test for '-o' at beginning
                $leading = 0 === strpos($value, '--') ? $value.'=' : $value;
                if ('' !== $leading && 0 === strpos($token, $leading)) {
                    return substr($token, \strlen($leading));
                }
            }
        }

        return $default;
    }

    /**
     * Returns the namespace part of the command name.
     *
     * This method is not part of public API and should not be used directly.
     *
     * @param string $name  The full name of the command
     * @param string $limit The maximum number of parts of the namespace
     *
     * @return string
     */
    public function extractNamespace($name, $limit = null)
    {
        $parts = explode(':', $name);
        array_pop($parts);

        return implode(':', null === $limit ? $parts : \array_slice($parts, 0, $limit));
    }

    /**
     * Execute command.
     *
     * @param string $command command name
     */
    public function execute($command)
    {
        if (!$command) {
            $command = $this->getDefaultCommand();
        }

        if (!isset($this->commands[$command])) {
            return $this->showCommandsLike($command);
        }

        if (array_key_exists('help', $this->options) || $this->optionAlias('h')) {
            return $this->showHelp($command);
        }

        if (array_key_exists('debug', $this->options) || $this->optionAlias('v')) {
            $this->verbose = true;
        }

        if (array_key_exists('ansi', $this->options)) {
            $this->getColors()->setForceStyle(true) ?? $this->getColors()->enable();
        }

        if (array_key_exists('no-ansi', $this->options)) {
            $this->getColors()->disable();
        }

        // Deprecated option '--loglevel', will be removed in version 1.7
        if (array_key_exists('loglevel', $this->options)) {
            throw new DeprecatedException(['--loglevel' => '--debug or -v']);
        }

        try {
            $handler = $this->commands[$command]['handler'];
            $arguments = $this->validateAndResolveArguments($command);
            $this->validateAndResolveOptions($command);

            if ($handler instanceof \Closure) {
                $handler = $handler->bindTo($this);
            }

            call_user_func_array($handler, $arguments);
        } catch (ConsoleLiteException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Write in a text.
     *
     * @param string $messages
     * @param string $fgColor
     * @param string $bgColor
     * @param array  $context
     */
    public function write($messages, $style = 'none')
    {
        if (!is_iterable($messages)) {
            $messages = [$this->interpolate($messages)];
        }

        foreach ($messages as $message) {
            if ($style) {
                $message = $this->style($message, $style);
            }
            $this->_doWrite($message, 'normal');
        }
    }

    /**
     * The output write handler.
     *
     * @param string|array $messages
     * @param string       $styles
     * @param string       $options
     */
    protected function _doWrite($messages, $options = 'normal')
    {
        $messages = (array) $messages;

        switch ($options) {
            case 'normal':
            $message = $messages;
                break;
            case 'debug':
                if ($this->isVerbose()) {
                    $message = $messages;
                }
                break;
        }

        foreach ($message as $key) {
            if ($this->hasStdoutSupport()) {
                return $this->getSilencer()->call('fwrite', STDOUT, $key);
            }
            echo $this->interpolate($key);
        }
    }

    /**
     * Write text line.
     *
     * @param string $message
     * @param string $fgColor
     * @param string $bgColor
     * @param array  $context
     */
    public function writeln($message, $style = 'none')
    {
        return $this->write($message, $style).$this->newLine();
    }

    /**
     * Write a hidden debug message.
     *
     * @param string $message
     * @param bool   $line
     *
     * @return string
     */
    public function writeDebug(string $message, bool $line = true)
    {
        return $line !== false ? $this->_doWrite($message) : $this->_doWrite($message).$this->newLine();
    }

    /**
     * Enter a number of empty lines.
     *
     * @param int   $num     Number of lines to output
     * @param bool  $line    draws a formatter line
     * @param array $context
     */
    public function newLine(int $num = 1, $line = false, $format = '+')
    {
        // Do it once or more, write with empty string gives us a new line
        for ($i = 0; $i < $num; $i++) {
            if (false === $line) {
                $this->write(PHP_EOL, null);
            } else {
                $this->write(str_pad('', $this->getMaxWidth(), $format)."\n", null);
            }
        }
    }

    /**
     * Beeps a certain number of times.
     *
     * @param int $num The number of times to beep
     */
    public function beep(int $num = 1)
    {
        $this->write(str_repeat("\x07", $num));
    }

    /**
     * Shorten the length of a sentence to the terminal's width
     * including a line break.
     *
     * @param string $message
     * @param bool   $newLine
     *
     * @return string
     */
    public function writeShort(string $message)
    {
        $width = $this->getWidth();

        if ($this->getWidth() > 80) {
            $width = 85;
        }

        $message = preg_replace("/(?<=.{{$width}})(.+)/", '...', $message);

        return $this->writeln(trim($message));
    }

    /**
     * Write error message.
     *
     * @param string $message
     * @param string $width
     * @param bool   $exit
     */
    public function errorBlock($message)
    {
        $this->block($message, ['white', 'bg_red', 'blink']);
    }

    /**
     * Write sucess message.
     *
     * @param string $message
     * @param string $width
     * @param bool   $exit
     */
    public function successBlock($message)
    {
        $this->block($message, ['white', 'bg_green', 'bold']);
    }

    /**
     * Wraps a text with a line formatter given line breaks.
     *
     * @param string $message
     * @param string $fgColor
     * @param string $bgColor
     * @param string $width
     */
    public function block($message, $styles = ['white', 'bg_blue'])
    {
        $size = strlen($message);
        $spaces = str_repeat(' ', $size);

        $this->newLine();
        $this->writeln(sprintf('  %s  ', $spaces), $styles);
        $this->writeln(sprintf('  %s  ', $message), $styles);
        $this->writeln(sprintf('  %s  ', $spaces), $styles);
        $this->newLine();
    }

    /**
     * Write a help block.
     *
     * @param mixed  $name        is the subject
     * @param string $description is the description
     * @param mixed  $nwid        is the subject's width
     * @param mixed  $dwid        is the description width
     */
    public function helpBlock(array $message, $spaces = 2)
    {
        $maxLen = 0;

        foreach (array_keys($message) as $name) {
            if (strlen($name) > $maxLen) {
                $maxLen = strlen($name);
            }
        }
        $pad = $maxLen + 3;

        foreach ($message as $Name => $Desc) {
            $space = ' ';
            $this->write(str_repeat(' ', $spaces - strlen($space)));
            $this->write($this->style($Name, 'green').str_repeat(' ', $pad - strlen($Name)));
            $this->writeShort(str_repeat(' ', $spaces - strlen($Name)).$Desc);
            $this->newLine();
        }
    }

    /**
     * Colors or styles an output text.
     *
     * @param string       $text
     * @param string|array $styles
     *
     * @return string
     */
    public function style($text, $styles = 'none')
    {
        return $this->getColors()->apply($styles, $text);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param $message
     * @param array $context
     *
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{'.$key.'}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Waits a certain number of seconds, optionally showing a wait message and
     * waiting for a enter key press.
     *
     * @param int  $seconds   Number of seconds
     * @param bool $countdown Show a countdown or not
     */
    public function wait(int $seconds = 0, bool $countdown = false)
    {
        if ($countdown === true) {
            $time = $seconds;

            while ($time > 0) {
                //$this->write($time.'...');
                $this->showProgress($time);
                sleep(1);
                $time--;
            }
            $this->newLine();
        } else {
            if ($seconds > 0) {
                sleep($seconds);
            } else {
                $this->getColors()->isEnabled() ?
                $this->block('Press enter key to continue...') :
                $this->writeln('Press enter key to continue...');

                $this->prompt();
            }
        }
    }

    /**
     * Displays a progress bar on the CLI. You must call it repeatedly
     * to update it. Set $thisStep = false to erase the progress bar.
     *
     * @param int|bool $thisStep
     * @param int      $totalSteps
     * @param string   $info
     */
    public function showProgress($thisStep = 1, int $totalSteps = 10, $info = '', $width = 50)
    {
        $inProgress = true;

        // restore cursor position when progress is continuing.
        if ($inProgress !== false && $inProgress <= $thisStep) {
            $this->getColors()->isEnabled() ? $this->write("\033[1A") : '';
        }
        $inProgress = $thisStep;

        if ($thisStep !== false) {
            // Don't allow div by zero or negative numbers....
            $thisStep = abs($thisStep);
            $totalSteps = $totalSteps < 1 ? 1 : $totalSteps;

            $percent = intval(($thisStep / $totalSteps) * 100);
            $step = (int) round(($width * $percent) / 100);

            // Write the progress bar
            if ($this->getColors()->isEnabled()) {
                $this->write($percent.'% ['.str_repeat('■', $step).''.str_repeat('-', $width - $step).']', 'green');
            } else {
                $wid = $width - 5;
                $perc = round(($thisStep * 100) / $totalSteps);
                $bar = round(($wid * $perc) / 100);

                if ($inProgress === false) {
                    return $this->writeln(sprintf("%s%% [%s>%s]  %s\r", $perc, str_repeat('=', $bar), str_repeat('-', $wid - $bar), $info));
                }

                return $this->write(sprintf("%s%% [%s>%s]  %s\r", $perc, str_repeat('=', $bar), str_repeat('-', $wid - $bar), $info));
            }
            // Textual representation...
            $this->writeln('  '.$info, 'bold');
        } else {
            $this->write("\007");
        }
    }

    /**
     * show a spinner icon message.
     *
     * @param string $msg
     * @param bool   $ended
     */
    public function showSpinner(string $msg = '', $ended = false)
    {
        static $chars = '-\|/';
        static $counter = 0;
        static $lastTime = null;

        $tpl = ($this->getColors()->isEnabled() ? "\x0D\x1B[2K" : "\x0D\r").'%s';

        if ($ended) {
            $this->getSilencer()->call('printf', $tpl, $msg);

            return;
        }

        $now = \microtime(true);

        if (null === $lastTime || ($lastTime < $now - 0.1)) {
            $lastTime = $now;
            // echo $chars[$counter];
            $this->getSilencer()->call('printf', $tpl, $chars[$counter].$msg);
            $counter++;

            if ($counter > \strlen($chars) - 1) {
                $counter = 0;
            }
        }
    }

    /**
     * Parse Command Definition.
     *
     * @param array $command
     *
     * @return array
     */
    protected function _parseCommand($command)
    {
        $exp = explode(' ', trim($command), 2);
        $command = trim($exp[0]);
        $args = [];
        $options = [];

        if (isset($exp[1])) {
            preg_match_all("/\{(?<name>\w+)(?<arr>\*)?((=(?<default>[^\}]+))|(?<optional>\?))?(::(?<desc>[^}]+))?\}/i", $exp[1], $matchArgs);
            preg_match_all("/\{--((?<alias>[a-zA-Z])\|)?(?<name>\w+)((?<valuable>=)(?<default>[^\}]+)?)?(::(?<desc>[^}]+))?\}/i", $exp[1], $matchOptions);
            foreach ($matchArgs['name'] as $i => $argName) {
                $default = $matchArgs['default'][$i];
                $expDefault = explode('::', $default, 2);
                if (count($expDefault) > 1) {
                    $default = $expDefault[0];
                    $description = $expDefault[1];
                } else {
                    $default = $expDefault[0];
                    $description = $matchArgs['desc'][$i];
                }

                $args[$argName] = [
                    'is_array'    => !empty($matchArgs['arr'][$i]),
                    'is_optional' => !empty($matchArgs['optional'][$i]) || !empty($default),
                    'default'     => $default ?: null,
                    'description' => $description,
                ];
            }

            foreach ($matchOptions['name'] as $i => $optName) {
                $default = $matchOptions['default'][$i];
                $expDefault = explode('::', $default, 2);
                if (count($expDefault) > 1) {
                    $default = $expDefault[0];
                    $description = $expDefault[1];
                } else {
                    $default = $expDefault[0];
                    $description = $matchOptions['desc'][$i];
                }
                $options[$optName] = [
                    'is_valuable' => !empty($matchOptions['valuable'][$i]),
                    'default'     => $default ?: null,
                    'description' => $description,
                    'alias'       => $matchOptions['alias'][$i] ?: null,
                ];
            }
        }

        return [$command, $args, $options];
    }

    /**
     * Parse PHP argv.
     *
     * @param array $argv
     *
     * @return array
     */
    protected function _parseArgv(array $argv)
    {
        $filename = array_shift($argv);
        $command = array_shift($argv);
        $arguments = [];
        $options = [];
        $optionsAlias = [];

        while (count($argv)) {
            $arg = array_shift($argv);
            if ($this->_isOption($arg)) {
                $optName = ltrim($arg, '-');
                if ($this->_isOptionWithValue($arg)) {
                    list($optName, $optvalue) = explode('=', $optName);
                } else {
                    $optvalue = array_shift($argv);
                }

                $options[$optName] = $optvalue;
            } elseif ($this->_isOptionAlias($arg)) {
                $alias = ltrim($arg, '-');
                $exp = explode('=', $alias);
                $aliases = str_split($exp[0]);
                if (count($aliases) > 1) {
                    foreach ($aliases as $aliasName) {
                        $optionsAlias[$aliasName] = null;
                    }
                } else {
                    $aliasName = $aliases[0];
                    if (count($exp) > 1) {
                        list($aliasName, $aliasValue) = $exp;
                    } else {
                        $aliasValue = array_shift($argv);
                    }

                    $optionsAlias[$aliasName] = $aliasValue;
                }
            } else {
                $arguments[] = $arg;
            }
        }

        return [$filename, $command, $arguments, $options, $optionsAlias];
    }

    /**
     * Check whether argument is option or not.
     *
     * @param string $arg
     *
     * @return bool
     */
    protected function _isOption($arg)
    {
        return (bool) preg_match("/^--\w+/", $arg);
    }

    /**
     * Check whether argument is option alias or not.
     *
     * @param string $arg
     *
     * @return bool
     */
    protected function _isOptionAlias($arg)
    {
        return (bool) preg_match('/^-[a-z]+/i', $arg);
    }

    /**
     * Check whether argument is option with value or not.
     *
     * @param string $arg
     *
     * @return bool
     */
    protected function _isOptionWithValue($arg)
    {
        return strpos($arg, '=') !== false;
    }

    /**
     * Validate And Resolve Arguments.
     *
     * @param string $command
     *
     * @return array resolved arguments
     */
    protected function validateAndResolveArguments($command)
    {
        $args = $this->arguments;
        $commandArgs = $this->commands[$command]['args'];
        $resolvedArgs = [];

        foreach ($commandArgs as $argName => $argOption) {
            if (!$argOption['is_optional'] && empty($args)) {
                return $this->getColors()->isEnabled() ?
                $this->errorBlock("Argument {$argName} is required") :
                $this->writeln(sprintf('Arguement %s is required', $argName));
            }
            if ($argOption['is_array']) {
                $value = $args;
            } else {
                $value = array_shift($args) ?: $argOption['default'];
            }

            $resolvedArgs[$argName] = $value;
        }

        return $resolvedArgs;
    }

    /**
     * Validate And Resolve Options.
     *
     * @param string $command
     */
    protected function validateAndResolveOptions($command)
    {
        $options = $this->options;
        $optionsAlias = $this->optionsAlias;
        $commandOptions = $this->commands[$command]['options'];
        $resolvedOptions = $options;

        foreach ($commandOptions as $optName => $optionSetting) {
            $alias = $optionSetting['alias'];
            if ($alias && array_key_exists($alias, $optionsAlias)) {
                $value = array_key_exists($alias, $optionsAlias) ? $optionsAlias[$alias] : $optionSetting['default'];
            } else {
                $value = array_key_exists($optName, $options) ? $options[$optName] : $optionSetting['default'];
            }

            if (!$optionSetting['is_valuable']) {
                $resolvedOptions[$optName] = array_key_exists($alias, $optionsAlias) || array_key_exists($optName, $options);
            } else {
                $resolvedOptions[$optName] = $value;
            }
        }

        $this->resolvedOptions = $resolvedOptions;
    }

    /**
     * Show commands like given command.
     *
     * @param string $keyword
     */
    protected function showCommandsLike($keyword)
    {
        $matchedCommands = $this->getCommandsLike($keyword);

        if (count($matchedCommands) === 1) {
            $keys = array_keys($matchedCommands);
            $values = array_values($matchedCommands);
            $name = array_shift($keys);
            $command = array_shift($values);
            $this->newLine();
            if ($this->confirm($this->newLine()." Command '{$keyword}' is not available. Did you mean '{$name}'?")) {
                $this->execute($name);
            } else {
                $commandList = $this->commands['list']['handler'];
                $commandList(count($matchedCommands) ? $keyword : null);
            }
        } else {
            $commandList = $this->commands['list']['handler'];
            $commandList(count($matchedCommands) ? $keyword : null);
            $this->getColors()->isEnabled() ?
            $this->errorBlock(" Command '{$keyword}' is not available.") :
            $this->writeln(" Command '{$keyword}' is not available.");
        }
    }

    /**
     * Show command help.
     *
     * @param string $commandName
     */
    public function showHelp($commandName)
    {
        $command = $this->commands[$commandName];
        $maxLen = 0;
        $args = $command['args'];
        $opts = $command['options'];
        $usageArgs = [$commandName];
        $displayArgs = [];
        $displayOpts = [];
        foreach ($args as $argName => $argSetting) {
            $usageArgs[] = '['.$argName.']';
            $displayArg = $argName;
            if ($argSetting['is_optional']) {
                $displayArg .= ' [optional]';
            }
            if (strlen($displayArg) > $maxLen) {
                $maxLen = strlen($displayArg);
            }
            $displayArgs[$displayArg] = $argSetting['description'];
        }
        $usageArgs[] = '[options]';

        foreach ($opts as $optName => $optSetting) {
            $displayOpt = $optSetting['alias'] ? str_pad('-'.$optSetting['is_valuable'].',', 1) : str_repeat(' ', 1);
            $displayOpt .= '--'.$optName;
            $displayOpt .= $optSetting['is_valuable'] ? ' [input]' : '';
            if (strlen($displayOpt) > $maxLen) {
                $maxLen = strlen($displayOpt);
            }
            $displayOpts[$displayOpt] = $optSetting['description'];
        }

        $pad = $maxLen + 3;

        if ('ConsoleLite Application - Version' === $this->getName()) {
            $this->robot(sprintf('How to get started with command -> %s', $commandName));
        }

        $this->writeln($this->style('Help:', 'purple'));
        $this->writeln(' '.$this->formatter->wordwrap($command['description'], 100));
        $this->newLine();
        $this->writeln($this->style('Usage:', 'purple'));
        $this->writeln('  '.implode(' ', $usageArgs));
        $this->newLine();
        $this->writeln($this->style('Arguments: ', 'purple'));
        foreach ($displayArgs as $argName => $argDesc) {
            $this->writeln('  '.$this->style($argName, 'green').str_repeat(' ', $pad - strlen($argName)).$argDesc);
        }
        $this->writeln('');
        $this->writeln($this->style('Options: ', 'purple'));
        foreach ($displayOpts as $optName => $optDesc) {
            $this->writeln('  '.$this->style($optName, 'green').str_repeat(' ', $pad - strlen($optName)).$optDesc);
        }
        $this->newLine();
    }

    /**
     * Stringify value.
     */
    protected function stringify($value)
    {
        if (is_object($value)) {
            return get_class($value);
        } elseif (is_array($value)) {
            if (count($value) > 3) {
                return 'Array';
            } else {
                return implode(', ', array_map([$this, 'stringify'], $value));
            }
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            return '"'.$this->getSilencer()->call('addslashes', $value).'"';
        } elseif (null === $value) {
            return 'null';
        } else {
            return $value;
        }
    }

    /**
     * Error Handler.
     *
     * @param Exception $exception
     */
    public function handleError(\Throwable $exception)
    {
        $this->setMaxWidth('100');
        $indent = str_repeat(' ', 2);
        //$class = get_class($exception);
        if (get_class($exception) === 'BiuradPHP\Toolbox\ConsoleLite\Exceptions\ConsoleLiteException') {
            $class = 'ApplicationException';
        } elseif (get_class($exception) === 'BiuradPHP\Toolbox\ConsoleLite\Exceptions\DeprecatedException') {
            $class = 'DeprecatedException';
        } elseif (get_class($exception) === 'BiuradPHP\Toolbox\ConsoleLite\Exceptions\ExpectedException') {
            $class = 'Logical/ExpectedException';
        } else {
            $class = get_class($exception);
        }
        $file = $exception->getFile();
        $line = $exception->getLine();
        $filepath = function ($file) {
            return str_replace(dirname(__DIR__).DIRECTORY_SEPARATOR, '', $file);
        };
        $message = $exception->getMessage();

        $this->newLine(1, true, '-');
        $this->writeln("Whoops! You got a(n) {$class}", 'none');
        $this->newLine();
        $this->writeln($this->formatter->wordwrap($message, 95), 'light_red');
        $this->newLine(1, true, '-');
        $this->newLine();

        $highlighter = new Highlighter($this->getColors());

        $fileContent = $this->getSilencer()->call('file_get_contents', $file);
        $this->writeln($highlighter->getCodeSnippet($fileContent, $line, 3), null);
        $this->writeln($indent.'File: '.$filepath($file), 'dark_gray');
        $this->writeln($indent.'Line: '.$line, 'dark_gray');

        $traces = $exception->getTrace();
        $count = count($traces);
        $traceFunction = function ($trace) {
            $args = implode(', ', array_map([$this, 'stringify'], $trace['args']));
            if ($trace['function'] == '{closure}') {
                return 'Closure('.$args.')';
            } elseif (!isset($trace['class'])) {
                return $trace['function'].'('.$args.')';
            } else {
                return $trace['class'].$trace['type'].$trace['function'].'('.$args.')';
            }
        };
        $x = $count > 9 ? 2 : 1;

        $this->newLine(2);
        $this->writeln($indent.'Traces:', 'light_red');
        $this->newLine();
        foreach ($traces as $i => $trace) {
            $space = str_repeat(' ', $x + 2);
            $no = str_pad($count - $i, $x, ' ', STR_PAD_LEFT);
            $func = $traceFunction($trace);
            $file = isset($trace['file']) ? $filepath($trace['file']) : 'unknown';
            $line = isset($trace['line']) ? $trace['line'] : 'unknown';
            $this->writeln("{$indent}{$no}) {$func}");
            $this->writeln("{$indent}{$space}File: {$file}", 'dark_gray');
            $this->writeln("{$indent}{$space}Line: {$line}", 'dark_gray');
            $this->newLine();
        }
    }

    /**
     * Run app.
     */
    public function run()
    {
        return $this->execute($this->command);
    }
}
