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

use BiuradPHP\Toolbox\ConsoleLite\Concerns\Silencer;
use BiuradPHP\Toolbox\FilePHP\FileHandler;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use UnexpectedValueException;

/**
 * ConsoleLite Terminal.
 *
 * This class is to read the given terminal
 * which consolelite will be running in.
 * e.g. reads the width, environment and
 * color support of the command-line interface.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class Terminal
{
    private static $width;
    private static $height;

    /** @var Silencer */
    private $silencer;

    /** @var FileHandler */
    private $filehandler;

    protected $stream;

    /** @var string border between columns */
    protected $border = ' ';

    /** @var int the terminal width */
    protected $max = 95;

    /** @var string Minimum PHP version */
    protected $phpversion = '7.1';

    private const JSON_UNESCAPED_SLASHES = 64;
    private const JSON_PRETTY_PRINT = 128;
    private const JSON_UNESCAPED_UNICODE = 256;

    private const CONSOLELITE_FILE_NAME = '/clite.json';
    private const CONSOLELITE_SCHEMA_FILE = '/../bin/clite.schema.json';

    public function __construct()
    {
        // try to get terminal width
        $width = $this->getTerminalWidth();
        if ($width) {
            $this->max = $width - 1;
        }

        $this->silencer = new Silencer();
        $this->filehandler = new FileHandler();

        $exists = $this->getSilencer()->call('file_exists', getcwd().self::CONSOLELITE_FILE_NAME);
        if (!$exists) {
            $this->generate_clite();
        }

        if (phpversion() < $this->phpversion) {
            die("\n\r".'Your PHP version must be '.$this->phpversion.
                ' or higher to run ConsoleLite. Current version: '.phpversion()."\n\r");
        }
    }

    /**
     * This a ConsoleLite Robot.
     *
     * Original from fuelphp
     *
     * @return string
     */
    public function robot($message = 'ConsoleLite Robot', $color = 'blue')
    {
        $eye = $this->style('*', 'green');

        return $this->writeln(sprintf("
                    $message
                  _____     /
                 /_____\\
            ____[\\%s---%s".$this->style('/]____
           /\\ #\\ \\_____/ /# /\\
          /  \\# \\_.---._/ #/  \\
         /   /|\\  |   |  /|\\   \\
        /___/ | | |   | | | \\___\\', [$color, 'blink']), $eye, $eye), [$color, 'italic']).
        $this->newLine();
    }

    /**
     * The currently set border (defaults to ' ').
     *
     * @return string
     */
    public function getBorder()
    {
        return $this->border;
    }

    /**
     * Set the border. The border is set between each column. Its width is
     * added to the column widths.
     *
     * @param string $border
     */
    public function setBorder($border)
    {
        $this->border = $border;
    }

    /**
     * Width of the terminal in characters.
     *
     * initially autodetected
     *
     * @return int
     */
    public function getMaxWidth()
    {
        return (int) $this->max;
    }

    /**
     * Set the width of the terminal to assume (in characters).
     *
     * @param int $max
     */
    public function setMaxWidth($max)
    {
        $this->max = (int) $max;
    }

    /**
     * Gets the terminal width.
     *
     * @return int
     */
    public function getWidth()
    {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int) trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?: 80;
    }

    /**
     * Tries to figure out the width of the terminal.
     *
     * @return int terminal width, 0 if unknown
     */
    public function getTerminalWidth()
    {
        // from environment
        if (isset($_SERVER['COLUMNS'])) {
            return (int) $_SERVER['COLUMNS'];
        }

        // via tput
        $process = proc_open('tput cols', [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        $width = (int) stream_get_contents($pipes[1]);
        proc_close($process);

        return $width;
    }

    /**
     * Gets the terminal height.
     *
     * @return int
     */
    public function getHeight()
    {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int) trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?: 50;
    }

    public function initDimensions()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int) $matches[1];
                self::$height = isset($matches[4]) ? (int) $matches[4] : (int) $matches[2];
            } elseif (null !== $dimensions = $this->getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int) $dimensions[0];
                self::$height = (int) $dimensions[1];
            }
        } elseif ($sttyString = $this->getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            } elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            }
        }
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return int[]|null An array composed of the width and the height or null if it could not be parsed
     */
    public function getConsoleMode()
    {
        if (!\function_exists('proc_open')) {
            return;
        }

        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (\is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            $this->getSilencer()->call('fclose', $pipes[1]);
            $this->getSilencer()->call('fclose', $pipes[2]);
            proc_close($process);

            if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                return [(int) $matches[2], (int) $matches[1]];
            }
        }
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     *
     * @return string|null
     */
    public function getSttyColumns()
    {
        if (!\function_exists('proc_open')) {
            return;
        }

        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (\is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            $this->getSilencer()->call('fclose', $pipes[1]);
            $this->getSilencer()->call('fclose', $pipes[2]);
            $this->getSilencer()->call('proc_close', $process);

            return $info;
        }
    }

    public function hasColorSupport()
    {
        if ('Hyper' === getenv('TERM_PROGRAM')) {
            return true;
        }

        if (\DIRECTORY_SEPARATOR === '\\') {
            return (\function_exists('sapi_windows_vt100_support')
                && @sapi_windows_vt100_support($this->stream))
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        if (\function_exists('stream_isatty')) {
            return @stream_isatty($this->stream);
        }

        if (\function_exists('posix_isatty')) {
            return @posix_isatty($this->stream);
        }

        $stat = @$this->getSilencer()->call('fstat', $this->stream);
        // Check if formatted mode is S_IFCHR
        return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * @return bool
     */
    public function hasStdoutSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     *
     * @return bool
     */
    public function hasStderrSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     *
     * @param int|resource $fileDescriptor
     *
     * @return bool
     */
    public static function isInteractive($fileDescriptor): bool
    {
        return \function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * Write a Sprintf message.
     *
     * Does not support color,
     * use style() function inside it for styles.
     *
     * @param string       $message
     * @param array|string $args
     * @param array|string $_
     *
     * @return void
     */
    public function writeSprint(string $message, $args, $_ = null)
    {
        return \printf($message, $args, $_);
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     *
     * @return bool
     */
    public function isRunningOS400()
    {
        $checks = [
            \function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS,
        ];

        return false !== stripos(implode(';', $checks), 'OS400');
    }

    /**
     * @return resource
     */
    public function openOutputStream()
    {
        $outputStream = $this->hasStdoutSupport() ? 'php://stdout' : 'php://output';

        return @$this->getSilencer()->call('fopen', $outputStream, 'w') ?:
        $this->getSilencer()->call('fopen', 'php://output', 'w');
    }

    /**
     * @return resource
     */
    public function openErrorStream()
    {
        $errorStream = $this->hasStderrSupport() ? 'php://stderr' : 'php://output';

        return $this->getSilencer()->call('fopen', $errorStream, 'w');
    }

    /**
     * Check whether OS is windows.
     *
     * @return bool
     */
    public function isWindows()
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD') || strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return '\\' === DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Checks whether OS is Linux.
     *
     * @return bool
     */
    public function isLinux()
    {
        if (PHP_OS === 'Linux') {
            return '/' === DIRECTORY_SEPERATOR;
        }
    }

    /**
     * Call the silencer class.
     *
     * @return Silencer
     */
    public function getSilencer()
    {
        return $this->silencer;
    }

    /**
     * Call the filephp class.
     *
     * @return \BiuradPHP\Toolbox\FilePHP\FileHandler
     */
    public function getFilehandler()
    {
        return $this->filehandler;
    }

    /**
     * The Json encoder for clite.json.
     *
     * @param string|resource|array $data
     * @param int                   $options
     */
    public function json_encode($data, $options = 448)
    {
        if (PHP_VERSION_ID >= 50400) {
            $json = \json_encode($data, $options);
            if (false === $json) {
                $this->json_error(\json_last_error());
            }

            if (PHP_VERSION_ID < 50428 || (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50512) || (defined('JSON_C_VERSION') && version_compare(phpversion('json'), '1.3.6', '<'))) {
                $json = preg_replace('/\[\s+\]/', '[]', $json);
                $json = preg_replace('/\{\s+\}/', '{}', $json);
            }

            return $json;
        }

        $json = \json_encode($data);
        if (false === $json) {
            $this->json_error(\json_last_error());
        }

        $prettyPrint = (bool) ($options & self::JSON_PRETTY_PRINT);
        $unescapeUnicode = (bool) ($options & self::JSON_UNESCAPED_UNICODE);
        $unescapeSlashes = (bool) ($options & self::JSON_UNESCAPED_SLASHES);

        if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes) {
            return $json;
        }

        return $this->parse_json($json, $unescapeUnicode, $unescapeSlashes);
    }

    public function json_decode($data, $assoc = true, $depth = '512', $options = 0)
    {
        $json = \json_decode($data, $assoc, $depth, $options);
        if (false === $json) {
            $this->json_error(\json_last_error());
        } else {
            return $json;
        }
    }

    /**
     * Json error.
     *
     * @param int $code
     *
     * @return \RuntimeException
     */
    private function json_error($code)
    {
        switch ($code) {
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $msg = 'Unknown error';
        }

        throw new \RuntimeException('JSON encoding failed: '.$msg);
    }

    /**
     * The clite.json parser.
     *
     * @param string|resource|array $json
     * @param mixed                 $unescapeUnicode
     * @param mixed                 $unescapeSlashes
     *
     * @return string
     */
    public function parse_json($json, $unescapeUnicode, $unescapeSlashes)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $outOfQuotes = true;
        $buffer = '';
        $noescape = true;

        for ($i = 0; $i < $strLen; $i++) {
            $char = substr($json, $i, 1);

            if ('"' === $char && $noescape) {
                $outOfQuotes = !$outOfQuotes;
            }

            if (!$outOfQuotes) {
                $buffer .= $char;
                $noescape = '\\' === $char ? !$noescape : true;
                continue;
            } elseif ('' !== $buffer) {
                if ($unescapeSlashes) {
                    $buffer = str_replace('\\/', '/', $buffer);
                }

                if ($unescapeUnicode && function_exists('mb_convert_encoding')) {
                    $buffer = preg_replace_callback('/(\\\\+)u([0-9a-f]{4})/i', function ($match) {
                        $len = strlen($match[1]);

                        if ($len % 2) {
                            $code = hexdec($match[2]);

                            if (0xD800 <= $code && 0xDFFF >= $code) {
                                return $match[0];
                            }

                            return str_repeat('\\', $len - 1).mb_convert_encoding(
                                pack('H*', $match[2]),
                                'UTF-8',
                                'UCS-2BE'
                            );
                        }

                        return $match[0];
                    }, $buffer);
                }

                $result .= $buffer.$char;
                $buffer = '';
                continue;
            }

            if (':' === $char) {
                $char .= ' ';
            } elseif ('}' === $char || ']' === $char) {
                $pos--;
                $prevChar = substr($json, $i - 1, 1);

                if ('{' !== $prevChar && '[' !== $prevChar) {
                    $result .= $newLine;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
                } else {
                    $result = rtrim($result);
                }
            }

            $result .= $char;

            if (',' === $char || '{' === $char || '[' === $char) {
                $result .= $newLine;

                if ('{' === $char || '[' === $char) {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }

        return $result;
    }

    /**
     * Validates the syntax of a JSON string.
     *
     * @param string $json
     * @param string $file
     *
     * @throws \UnexpectedValueException
     * @throws ParsingException
     *
     * @return bool true on success
     */
    protected function validateSyntax($json, $file = null)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);
        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new UnexpectedValueException('"'.$file.'" is not UTF-8, could not parse as JSON');
            }

            return true;
        }

        throw new ParsingException('"'.$file.'" does not contain valid JSON'."\n".$result->getMessage(), $result->getDetails());
    }

    /**
     * This only loads a json file.
     *
     * @param string|null $file
     *
     * @return void
     */
    public function loadFile(?string $file = null)
    {
        if (null === $file) {
            $file = getcwd().self::CONSOLELITE_FILE_NAME;
        }

        $json = $this->decodeFile($file);

        return $json;
    }

    /**
     * Parses json string and returns hash.
     *
     * @param string $json json string
     * @param string $file the json file
     *
     * @return mixed
     */
    public function parseJson($json, $file = null)
    {
        if (null === $json) {
            return;
        }
        $data = $this->json_decode($json, true);
        if (null === $data && JSON_ERROR_NONE !== \json_last_error()) {
            $this->validateSyntax($json, $file);
        }

        return $data;
    }

    /**
     * @throws ParsingException
     *
     * @return array|string
     */
    public function decodeFile(string $file, bool $assoc = false)
    {
        $json = $this->getFilehandler()->getInstance($file)->get();

        return $this->parseJson($json, $assoc);
    }

    /**
     * Validates the decoded JSON data.
     *
     * @param string $schemaFile The JSON file
     *
     * @throws ValidationException If the JSON data failed validation
     */
    public function validate(string $schemaFile = null)
    {
        $content = getcwd().self::CONSOLELITE_FILE_NAME;
        $data = $this->loadFile($content);

        if (null === $schemaFile) {
            $schemaFile = __DIR__.self::CONSOLELITE_SCHEMA_FILE;
        }

        // Prepend with file:// only when not using a special schema already (e.g. in the phar)
        if (false === strpos($schemaFile, '://')) {
            $schemaFile = 'file://'.$schemaFile;
        }

        $schemaData = (object) ['$ref' => $schemaFile];

        $schemaData->additionalProperties = true;
        $schemaData->required = [];

        $validator = new Validator();
        $validator->check($data, $schemaData);

        if (!$validator->isValid()) {
            $errors = '';
            foreach ($validator->getErrors() as $error) {
                $errors .= $error['property'] ? $error['property'].' : ' : ''.$error['message'];
            }

            $e = preg_replace('/(?<=.{10})(.+)(?=.{10})/', '...', $content);

            throw new ValidationException('"'.$e.'" does not match the expected JSON schema '.$errors);
        }

        return true;
    }

    /**
     * Generate the ConsoleLite config file.
     */
    public function generate_clite()
    {
        // generate a clite config.
        $application = 'ConsoleLite Configuration';
        $date = date('Y/m/d h:i');
        $version = '1.0.0';

        // generate stuble config.
        $stuble = [];
        $stuble['name'] = 'ConsoleLite Stub Generator';

        // generate phar config.
        $phar = [];
        $phar['name'] = 'clite.phar';
        $phar['index'] = 'docs/tests/test';
        $phar['compression'] = 0;
        $phar['signature'] = null;

        $pack = [];
        $pack['autoload'] = 'vendor/autoload.php';
        $pack['bin'] = 'docs/tests/test';
        $pack['files'] = ['clite.json', 'composer.json'];
        $pack['directory'] = ['src/', 'vendor/'];
        $pack['excludes'] = ['Tests/*', '!*.php'];

        $unpack = [];
        $unpack['extract-to'] = 'unpack';
        $unpack['files'] = null;
        $unpack['overwrite'] = false;

        $destination = preg_replace('![\\\/]+!', '/', getcwd().'/clite.json');

        return $this->getFilehandler()->getInstance($destination)->put(
            $this->json_encode([
                'application' => $application,
                'generated'   => $date,
                'version'     => $version,
                'stuble'      => ['config' => $stuble],
                'compile'     => ['config' => $phar, 'pack' => $pack, 'unpack' => $unpack],
            ])
        );
    }
}
