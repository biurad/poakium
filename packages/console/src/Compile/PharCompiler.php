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

namespace BiuradPHP\Toolbox\ConsoleLite\Compile;

use BadMethodCallException;
use BiuradPHP\Toolbox\ConsoleLite\Exceptions\ConsoleLiteException;
use BiuradPHP\Toolbox\ConsoleLite\Terminal;
use FilesystemIterator;
use InvalidArgumentException;
use LogicException;
use Phar;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use UnexpectedValueException;

/**
 * The Compiler class creates PHAR archives.
 *
 * This file is from Phar Compiler Library (C) Christian Neff.
 * modified by Divine Niiquaye under opensource license ISC.
 *
 * @author   Fabien Potencier <fabien@symfony.com>
 * @author   Jordi Boggiano <j.boggiano@seld.be>
 * @author   Christian Neff <christian.neff@gmail.com>
 * @author   Divine Niiquaye <hello@biuhub.net>
 * @license  MIT
 */
class PharCompiler extends Terminal
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $index = [];

    /** @var array */
    private $supportedSignatureTypes = [
        \Phar::SHA512 => 1,
        \Phar::SHA256 => 1,
        \Phar::SHA1   => 1,
    ];

    /** @var resource */
    private $key = null;

    /** @var */
    private $signatureType;

    /**
     * @var int compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2
     */
    private $compressMode = 0;

    /**
     * @var string|null The latest commit id
     */
    private $version;

    /**
     * @var array Want to added files. (It is relative the)
     */
    private $files = [];

    /**
     * @var array Want to include files suffix name list
     */
    private $suffixes = ['.php'];

    /** @var string Set the excutable bin from your project's dirextory. */
    private $bin = null;

    /**
     * @var \Closure maybe you not want strip all files
     */
    private $stripFilter;

    /** @var int */
    private $counter = 0;

    protected $phar;
    protected $linter;
    protected $config;

    /**
     * Creates a Compiler instance.
     *
     * @param string $path The root path of the project
     *
     * @throws \RuntimeException if the creation of Phar archives is disabled in php.ini.
     */
    public function __construct($path = null)
    {
        parent::__construct();

        $this->phar = Phar::class;

        if (!\class_exists(Phar::class, false)) {
            throw new RuntimeException("The 'phar' extension is required for build phar package");
        }

        if (\ini_get('phar.readonly')) {
            throw new UnexpectedValueException("The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0'");
        }

        if ($path !== null) {
            $this->path = $path;
        }
    }

    /**
     * Compiles all files into a single PHAR file.
     *
     * @param string $outputfile The full name of the file to create
     *
     * @throws LogicException if no index files are defined
     */
    public function pack($outputfile)
    {
        if (empty($this->index)) {
            throw new LogicException('Cannot compile when no index files are defined.');
        }

        if ($this->getSilencer()->call('file_exists', $outputfile)) {
            $this->getSilencer()->call('unlink', $outputfile);
        }

        $name = $this->getSilencer()->call('basename', $outputfile);
        $phar = new Phar($outputfile, 0, $name);

        if ($this->key !== null) {
            $privateKey = '';
            \openssl_pkey_export($this->key, $privateKey);
            $keyDetails = \openssl_pkey_get_details($this->key);
            $phar->setSignatureAlgorithm(Phar::OPENSSL, $privateKey);
            $this->getSilencer()->call('file_put_contents', $outputfile.'.pubkey', $keyDetails['key']);
        } else {
            $phar->setSignatureAlgorithm($this->selectSignatureType());
        }

        $phar->startBuffering();

        foreach ($this->files as $virtualfile => $fileinfo) {
            list($realfile, $strip) = $fileinfo;
            $content = $this->getSilencer()->call('file_get_contents', $realfile);

            if ($strip) {
                $content = $this->stripWhitespace($content);
            }
            // clear php file comments
            if ($strip && \strpos($outputfile, $this->getSuffix())) {
                $filter = $this->stripFilter;

                if (!$filter || ($filter && $filter($outputfile))) {
                    $content = $this->stripWhitespace($content);
                }
            }

            $phar->addFromString($virtualfile, $content);
        }

        foreach ($this->index as $type => $fileinfo) {
            list($virtualfile, $realfile) = $fileinfo;
            $content = $this->getSilencer()->call('file_get_contents', $realfile);

            if ($type == 'cli') {
                $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
            }

            $phar->addFromString($virtualfile, $content);
        }

        $this->addBin($phar);

        $this->counter = $phar->count();

        if (isset($this->index['web'])) {
            $stub = $phar->createDefaultStub(null, $this->index['web'][0]);
        }
        if (isset($this->index['cli'])) {
            $stub = $this->generateStub($name);
        }
        $phar->setStub($stub);

        if ($this->compressMode) {
            $phar->compressFiles($this->compressMode);
        }

        $phar->stopBuffering();
        unset($phar);
    }

    /**
     * @param string            $pharFile
     * @param string            $extractTo
     * @param string|array|null $files     Only fetch the listed files
     * @param bool              $overwrite
     *
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     * @throws RuntimeException
     *
     * @return bool
     */
    public function unPack(string $pharFile, string $extractTo, $files = null, $overwrite = false): bool
    {
        $phar = new Phar($pharFile);

        // check whether the folder exists and delete it.
        $exists = $this->getSilencer()->call('file_exists', $extractTo);

        if (false === $overwrite) {
            if ($exists) {
                throw new ConsoleLiteException('Delete the previous extracted phar folder then try again');
            }
        }

        return $phar->extractTo($extractTo, $files, $overwrite);
    }

    /**
     * Gets the root path of the project.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    private function addBin($phar)
    {
        $content = $this->getSilencer()->call('file_get_contents', getcwd().DIRECTORY_SEPARATOR.$this->bin);
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString($this->bin, $content);
    }

    /**
     * Get the value of bin.
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * Set the value of bin.
     */
    public function setBin(string $file)
    {
        $this->bin = $file;
    }

    /**
     * Gets list of all added files.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the value of key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key.
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Call the Phar class.
     *
     * @return Phar
     */
    public function getPhar()
    {
        return $this->phar;
    }

    /**
     * @return int
     */
    private function selectSignatureType()
    {
        if (isset($this->supportedSignatureTypes[$this->signatureType])) {
            return $this->signatureType;
        }

        return Phar::SHA1;
    }

    /**
     * @return array
     */
    public function get_phar_signing_algorithms(): array
    {
        static $algorithms = [
            'MD5'     => Phar::MD5,
            'SHA1'    => Phar::SHA1,
            'SHA256'  => Phar::SHA256,
            'SHA512'  => Phar::SHA512,
            'OPENSSL' => Phar::OPENSSL,
        ];

        return $algorithms;
    }

    /**
     * Adds a file.
     *
     * @param string $file  The name of the file relative to the project root
     * @param bool   $strip Strip whitespace (Default: TRUE)
     */
    public function addFile($file, $strip = true)
    {
        $realfile = ($this->getPath().DIRECTORY_SEPARATOR.$file);
        $this->files[$file] = [$realfile, (bool) $strip];
    }

    /**
     * Adds files of the given directory recursively.
     *
     * @param string       $directory The name of the directory relative to the project root
     * @param string|array $exclude   List of file name patterns to exclude (optional)
     * @param bool         $strip     Strip whitespace (Default: TRUE)
     */
    public function addDirectory($directory, $exclude = null, $strip = true)
    {
        $realpath = ($this->getPath().DIRECTORY_SEPARATOR.$directory);
        $iterator = new RecursiveDirectoryIterator($realpath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);

        if ((is_string($exclude) || is_array($exclude)) && !empty($exclude)) {
            $iterator = new RecursiveCallbackFilterIterator($iterator, function ($current) use ($exclude, $realpath) {
                if ($current->isDir()) {
                    return true;
                }

                $subpath = substr($current->getPathName(), strlen($realpath) + 1);

                return $this->filter($subpath, (array) $exclude);
            });
        }

        $iterator = new RecursiveIteratorIterator($iterator);
        foreach ($iterator as $file) {
            $virtualfile = substr($file->getPathName(), strlen($this->getPath()) + 1);
            $this->addFile($virtualfile, $strip);
        }
    }

    /**
     * @param string|array $suffixes
     *
     * @return $this
     */
    public function getSuffix($suffixes = null)
    {
        if (null !== $suffixes) {
            $this->suffixes = \array_merge($this->suffixes, (array) $suffixes);
        }

        return $this->suffixes;
    }

    /**
     * Gets list of defined index files.
     *
     * @return array
     */
    public function getIndexFiles()
    {
        return $this->index;
    }

    /**
     * Adds an index file.
     *
     * @param string $file The name of the file relative to the project root
     * @param string $type The SAPI type (Default: 'cli')
     */
    public function addIndexFile($file, $type = 'cli')
    {
        $type = strtolower($type);

        if (!in_array($type, ['cli', 'web'])) {
            throw new InvalidArgumentException(sprintf('Index file type "%s" is invalid, must be one of: cli, web', $type));
        }

        $this->index[$type] = [$file, ($this->getPath().DIRECTORY_SEPARATOR.$file)];
    }

    /**
     * Gets list of all supported SAPIs.
     *
     * @return array
     */
    public function getSupportedSapis()
    {
        return array_keys($this->index);
    }

    /**
     * Returns whether the compiled program will support the given SAPI type.
     *
     * @param string $sapi The SAPI type
     *
     * @return bool
     */
    public function supportsSapi($sapi)
    {
        return in_array((string) $sapi, $this->getSupportedSapis());
    }

    /**
     * Get compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2.
     *
     * @return int
     */
    public function getCompressMode()
    {
        return $this->compressMode;
    }

    /**
     * Set compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2.
     *
     * @param int $compressMode compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2
     *
     * @return self
     */
    public function setCompressMode(int $compressMode)
    {
        $this->compressMode = $compressMode;

        return $this;
    }

    /**
     * @return array
     */
    public function getPrivateKey(): array
    {
        return [
            <<<'KEY'
-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,3FF97F75E5A8F534

TvEPC5L3OXjy4X5t6SRsW6J4Dfdgw0Mfjqwa4OOI88uk5L8SIezs4sHDYHba9GkG
RKVnRhA5F+gEHrabsQiVJdWPdS8xKUgpkvHqoAT8Zl5sAy/3e/EKZ+Bd2pS/t5yQ
aGGqliG4oWecx42QGL8rmyrbs2wnuBZmwQ6iIVIfYabwpiH+lcEmEoxomXjt9A3j
Sh8IhaDzMLnVS8egk1QvvhFjyXyBIW5mLIue6cdEgINbxzRReNQgjlyHS8BJRLp9
EvJcZDKJiNJt+VLncbfm4ZhbdKvSsbZbXC/Pqv06YNMY1+m9QwszHJexqjm7AyzB
MkBFedcxcxqvSb8DaGgQfUkm9rAmbmu+l1Dncd72Cjjf8fIfuodUmKsdfYds3h+n
Ss7K4YiiNp7u9pqJBMvUdtrVoSsNAo6i7uFa7JQTXec9sbFN1nezgq1FZmcfJYUZ
rdpc2J1hbHTfUZWtLZebA72GU63Y9zkZzbP3SjFUSWniEEbzWbPy2sAycHrpagND
itOQNHwZ2Me81MQQB55JOKblKkSha6cNo9nJjd8rpyo/lc/Iay9qlUyba7RO0V/t
wm9ZeUZL+D2/JQH7zGyLxkKqcMC+CFrNYnVh0U4nk3ftZsM+jcyfl7ScVFTKmcRc
ypcpLwfS6gyenTqiTiJx/Zca4xmRNA+Fy1EhkymxP3ku0kTU6qutT2tuYOjtz/rW
k6oIhMcpsXFdB3N9iHT4qqElo3rVW/qLQaNIqxd8+JmE5GkHmF43PhK3HX1PCmRC
TnvzVS0y1l8zCsRToUtv5rCBC+r8Q3gnvGGnT4jrsp98ithGIQCbbQ==
-----END RSA PRIVATE KEY-----
KEY
            ,
            '',
        ];
    }

    /**
     * Generates the stub.
     *
     * @param string $name The internal Phar name
     *
     * @return string
     */
    protected function generateStub($name)
    {
        $date = \date('Y-m-d H:i');

        $stub = ['#!/usr/bin/env php', '<?php'];
        $stub[] = '/**';
        $stub[] = "* @date $date";
        $stub[] = '* @version '.$this->getVersion();
        $stub[] = '*/';
        $stub[] = "define('IN_PHAR', true);"."\n";
        $stub[] = "Phar::mapPhar('$name');"."\n";
        $stub[] = "if (PHP_SAPI == 'cli') {";

        if (isset($this->index['cli'])) {
            $file = $this->index['cli'][0];
            $stub[] = "   require 'phar://$name/$file';";
        } else {
            $stub[] = "   exit('This program can not be invoked via the CLI version of PHP, use the Web interface instead.'.PHP_EOL);";
        }

        $stub[] = '} else {';

        if (isset($this->index['web'])) {
            $file = $this->index['web'][0];
            $stub[] = "   require 'phar://$name/$file';";
        } else {
            $stub[] = "   exit('This program can not be invoked via the Web interface, use the CLI version of PHP instead.'.PHP_EOL);";
        }

        $stub[] = '}'."\n";
        $stub[] = '__HALT_COMPILER();';

        return \implode("\n", $stub);
    }

    /**
     * Filters the given path.
     *
     * @param array $patterns
     *
     * @return bool
     */
    protected function filter($path, array $patterns)
    {
        foreach ($patterns as $pattern) {
            if ($pattern[0] == '!' ? !fnmatch(substr($pattern, 1), $path) : fnmatch($pattern, $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string|null $version
     *
     * @return self
     */
    public function setVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    protected function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}
