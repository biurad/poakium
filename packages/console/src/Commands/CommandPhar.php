<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command newLine.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\ConsoleLite\Commands;

use BiuradPHP\Toolbox\ConsoleLite\Command;
use BiuradPHP\Toolbox\ConsoleLite\Compile\PharCompiler;
use BiuradPHP\Toolbox\ConsoleLite\Compile\PharConfig;
use BiuradPHP\Toolbox\ConsoleLite\Exceptions\ConsoleLiteException;

/*
 * The ConsoleLite Compiler
 *
 * This class is a brief about info of consolelite
 * and the system consolelite is been runned in.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class CommandPhar extends Command
{
    protected $signature = 'compile 
    {name::This could be set to pack or unpack before inputting options}
    {--signature=::Set the signing signature of the phar. eg openssl.key}
    {--index=::Set the main file were the phar will be looaded from}
    {--compression=::Set the compression level for the phar. eg 0 or 4096}
    {--directory=::Set the directory where the phar is generated from}
    {--version=::Set the version of the project, so you stay up to date}
    {--bin=::Set the bin to be added to the compiled phar file}
    {--files=::Add the files that will be needed example, LICENSE file or composer.json}
    {--excludes=::Add the excluded files or directories from the compiled phar file}
    {--type=::Set the type of phar that should be generated cli or web, default is cli}
    {--extract=::Set the folder where the files and direcotries in phar will be extracted to}
    {--overwrite=::Allow the previous extracted folders to be overwritten}
    {--autoload=::Add the file which contains all the classes and namespaces for autoloading}';

    protected $description = 'Pack a project directory to phar or unpack phar to directory';

    public function handle($name)
    {
        // setup configs and phar...
        $config = new PharConfig();
        $compiler = new PharCompiler(getcwd());
        $clite = $config->loadFile();

        // setup clite configs for pack and upack...
        $version = $this->hasOption('version') ?: $clite['version'];
        $phar_name = $this->hasOption('name') ?: $clite['compile']['config']['name'];
        $index = $this->hasOption('index') ?: $clite['compile']['config']['index'];
        $signature = $this->hasOption('signature') ?: $clite['compile']['config']['signature'];
        $compression = $this->hasOption('compression') ?: $clite['compile']['config']['compression'];
        $autoload = $this->hasOption('autoload') ?: $clite['compile']['pack']['autoload'];
        $bin = $this->hasOption('bin') ?: $clite['compile']['pack']['bin'];
        $files = $this->hasOption('files') ?: $clite['compile']['pack']['files'];
        $directory = $this->hasOption('directory') ?: $clite['compile']['pack']['directory'];
        $excludes = $this->hasOption('excludes') ?: $clite['compile']['pack']['excludes'];

        $extract_to = $this->hasOption('extract') ?: $clite['compile']['unpack']['extract-to'];
        $ex_files = $this->hasOption('files') ?: $clite['compile']['unpack']['files'];
        $overwrite = $this->hasOption('overwrite') ?: $clite['compile']['unpack']['overwrite'];

        $type = $this->hasOption('type') ?: $clite['compile']['config']['type'];

        switch ($name) {
            case 'pack':
                // code...
                $this->getColors()->isEnabled() ?
                $this->block('==========: STARTING PHAR BUILD PROCESS :==========') :
                $this->writeln('==========: STARTING PHAR BUILD PROCESS :==========');
                $this->newLine();

                $phar_file = $this->getSilencer()->call('basename', $phar_name);

                try {
                    $compiler->addIndexFile($index, $type ?: 'cli');
                    if (is_array($directory)) {
                        foreach ($directory as $key) {
                            $compiler->addDirectory($key, $excludes);
                        }
                    } else {
                        $compiler->addDirectory($directory, $excludes);
                    }
                    if (is_array($files)) {
                        foreach ($files as $var) {
                            $compiler->addFile($var);
                        }
                    } else {
                        $compiler->addFile($files);
                    }
                    $compiler->setCompressMode($compression);
                    $compiler->setKey($signature);
                    $compiler->setVersion($version);
                    $compiler->setBin($bin);
                    $compiler->addFile($autoload);
                    $compiler->pack(getcwd().DIRECTORY_SEPARATOR.$phar_name);

                    $i = 0;
                    $total = $compiler->getCounter();
                    while ($i <= $total) {
                        $this->showProgress($i, $total, '🔨', 49);
                        usleep(30000);
                        $i++;
                    }
                    $this->showProgress($total, $total, 'Completed', 49);

                    $done = 'DONE: Found -> '.$compiler->getCounter().' files';
                } catch (ConsoleLiteException $ex) {
                    $done = 'ERROR: '.$ex->getMessage();
                }

                $phar_info = [
                    'Source Directory: ' => is_array($directory) ? implode(', ', array_values($directory)) : $directory,
                    'PHAR Name: '        => $phar_file,
                    'Phar Size '         => $this->getFileHandler()->getInstance($phar_name)->size(),
                    'Index Script: '     => 'phar://'."$phar_file/$index",
                    'Version '           => $version,
                    'Process: '          => $done,
                ];
                $this->newLine(2);
                $this->helpblock($phar_info);
                break;
            case 'unpack':
                // code...
                $this->getColors()->isEnabled() ?
                $this->block('==========: STARTING PHAR UNPACK PROCESS :==========') :
                $this->writeln('==========: STARTING PHAR UNPACK PROCESS :==========');
                $this->newLine();

                try {
                    $compiler->unPack($phar_name, $extract_to, $ex_files, $overwrite);

                    $total = 1000;
                    while ($total--) {
                        $this->showSpinner('  '.'Unpacking files & directories... 🚚 ');
                        usleep(100);
                    }
                    $this->showSpinner('Extraction Completed ', true);

                    $un_done = 'DONE: Extracted';
                } catch (ConsoleLiteException $ex) {
                    $un_done = 'ERROR: '.$ex->getMessage();
                }

                $phar_info = [
                    'Extracted Directory: ' => is_array($extract_to) ? implode(', ', array_values($extract_to)) : $extract_to,
                    'Version '              => $version,
                    'Process: '             => $un_done,
                ];
                $this->newLine(2);
                $this->helpblock($phar_info);
                break;
        }
    }
}
