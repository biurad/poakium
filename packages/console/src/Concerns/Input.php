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

namespace BiuradPHP\Toolbox\ConsoleLite\Concerns;

use RuntimeException;

/**
 * ConsoleLite Input trait.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @author Muhammad Syifa <emsifa@gmail.com>
 * @license MIT
 */
trait Input
{
    protected $questionSuffix = '> ';

    protected $autocompleterValues;

    /**
     * Asking question.
     *
     * @param string $question
     * @param array  $default
     *
     * @return mixed|null
     */
    public function ask($question, $default = null)
    {
        if ($default) {
            $question = $question.' '.$this->style("[{$default}]", 'green');
        }
        if (\is_array($default)) {
            $maxWidth = max(array_map([$this, 'strlen'], array_keys($default)));

            $messages = (array) $question;
            foreach ($default as $key => $value) {
                $width = $maxWidth - $this->strlen($key);
                $messages[] = PHP_EOL.$this->style(' ['.$key.str_repeat('', $width).'] ', 'green').$value;
            }
            $question = $messages;
        }

        $this->writeln($question, 'light_blue').$this->write($this->questionSuffix);

        $handle = fopen('php://stdin', 'r');
        $answer = trim(fgets($handle, 4096));
        fclose($handle);

        return $answer ?? $default;
    }

    /**
     * Escapes trailing "\" in given text.
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     *
     * @internal
     */
    public function escapeTrailingBackslash($text)
    {
        if ('\\' === substr($text, -1)) {
            $len = \strlen($text);
            $text = rtrim($text, '\\');
            $text = str_replace("\0", '', $text);
            $text .= str_repeat("\0", $len - \strlen($text));
        }

        return $text;
    }

    /**
     * Gets values for the autocompleter.
     *
     * @return iterable|null
     */
    public function getAutocompleterValues()
    {
        return $this->autocompleterValues;
    }

    /**
     * Sets values for the autocompleter.
     *
     * @param iterable|null|array $values
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     *
     * @return $this
     */
    public function setAutocompleterValues($values)
    {
        if (\is_array($values)) {
            $values = $this->isAssoc($values) ? array_merge(array_keys($values), array_values($values)) : array_values($values);
        }

        if (null !== $values && !\is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentException('Autocompleter values can be either an array, "null" or a "Traversable" object.');
        }

        if ($this->hidden) {
            throw new LogicException('A hidden question cannot use the autocompleter.');
        }

        $this->autocompleterValues = $values;

        return $this;
    }

    /**
     * Prompts the user for input and shows what they type.
     *
     * @return string
     */
    public function prompt()
    {
        $stdin = fopen('php://stdin', 'r');
        $answer = $this->trimAnswer(fgets($stdin, 4096));
        fclose($stdin);

        return $answer;
    }

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param string $string The string to check its length
     *
     * @return int The length of the string
     */
    public static function strlen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return \strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * Returns the subset of a string, using mb_substr if it is available.
     *
     * @param string   $string String to subset
     * @param int      $from   Start offset
     * @param int|null $length Length to read
     *
     * @return string The string subset
     */
    public static function substr($string, $from, $length = null)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return substr($string, $from, $length);
        }

        return mb_substr($string, $from, $length, $encoding);
    }

    /**
     * Prompts the user for input and hides what they type.
     *
     * @param bool $allowFallback if prompting fails for any reason and this is set to true the prompt
     *                            will be done using the regular prompt() function, otherwise a
     *                            \RuntimeException is thrown
     *
     * @throws RuntimeException on failure to prompt, unless $allowFallback is true
     *
     * @return string
     */
    public function hiddenPrompt($allowFallback = true)
    {
        // handle windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            // fallback to hiddeninput executable
            $exe = __DIR__.'\\..\\resources\\hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5)) {
                $tmpExe = sys_get_temp_dir().'/hiddeninput.exe';

                // use stream_copy_to_stream instead of copy
                // to work around https://bugs.php.net/bug.php?id=64634
                $source = fopen($exe, 'r');
                $target = fopen($tmpExe, 'w+');
                stream_copy_to_stream($source, $target);
                fclose($source);
                fclose($target);
                unset($source, $target);

                $exe = $tmpExe;
            }

            $output = shell_exec($exe);

            // clean up
            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            if ($output !== null) {
                // output a newline to be on par with the regular prompt()
                $this->line();

                return $this->trimAnswer($output);
            }
        }

        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (['bash', 'zsh', 'ksh', 'csh', 'sh'] as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    $shell = $sh;
                    break;
                }
            }

            if (isset($shell)) {
                $readCmd = ($shell === 'csh') ? 'set mypassword = $<' : 'read -r mypassword';
                $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
                $output = shell_exec($command);

                if ($output !== null) {
                    // output a newline to be on par with the regular prompt()
                    $this->line();

                    return $this->trimAnswer($output);
                }
            }
        }

        // not able to hide the answer
        if (!$allowFallback) {
            throw new \RuntimeException('Could not prompt for input in a secure fashion, aborting');
        }

        return $this->prompt();
    }

    private function trimAnswer($str)
    {
        return preg_replace('{\r?\n$}D', '', $str);
    }

    /**
     * Input comfirmation.
     *
     * @param string $question
     * @param bool   $default
     */
    public function confirm($question, $default = false)
    {
        $availableAnswers = [
            'yes' => true,
            'no'  => false,
            'y'   => true,
            'n'   => false,
        ];

        $result = null;
        do {
            if ($default) {
                $suffix = $this->style('[', 'dark_gray').$this->style('Y', 'green').$this->style('/n]', 'dark_gray');
            } else {
                $suffix = $this->style('[y/', 'dark_gray').$this->style('N', 'green').$this->style(']', 'dark_gray');
            }
            $answer = $this->ask($question.' '.$suffix) ?: ($default ? 'y' : 'n');

            if (!isset($availableAnswers[$answer])) {
                $this->getColors()->isEnabled() ? $this->errorBlock('Please type: (y/n) or (yes/no)') :
                $this->newLine().
                $this->writeln('Please type: (y/n) or (yes/no)').
                $this->newLine();
            } else {
                $result = $availableAnswers[$answer];
            }
        } while (null === $result);

        return $availableAnswers[$answer];
    }

    /**
     * Select a choice.
     *
     * @param string $question
     * @param array  $available
     * @param string $choice1
     * @param string $choice2
     * @param string $errormessage
     */
    public function choice($question, $default = null, $choices = null, $errorMessage = '')
    {
        $defaultPrompt = $default ? $this->style("[{$default}]", 'green') : '';
        if ($choices) {
            $values = $choices ? $choices : $default ?? false;
        } else {
            $values = $default ?? '';
        }

        return $this->ask($question.' '.$defaultPrompt, $values);
    }

    protected function isAssoc($array)
    {
        return (bool) \count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Check whether Stty is available or not.
     *
     * @return bool
     */
    private function hasSttyAvailable()
    {
        if (null !== self::$stty) {
            return self::$stty;
        }
        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }

    /**
     * Returns a valid unix shell.
     *
     * @return string|bool The valid shell name, false in case no valid shell is found
     */
    private function getShell()
    {
        if (null !== self::$shell) {
            return self::$shell;
        }
        self::$shell = false;
        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (['bash', 'zsh', 'ksh', 'csh'] as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    self::$shell = $sh;
                    break;
                }
            }
        }

        return self::$shell;
    }
}
