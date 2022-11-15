<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Biurad\Git\Commit\Message;
use Biurad\Git\Repository;
use PHPUnit\Framework as t;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

if (!\function_exists('deleteDir')) {
    function deleteDir(string $dir): void
    {
        Process::fromShellCommandline(('\\' === \DIRECTORY_SEPARATOR ? 'rd /s /q "' : 'rm -rf "').$dir.'"')->mustRun();
    }
}

dataset('repo', [(static function (): Repository {
    $repo = new Repository(__DIR__.'/build/repo');

    if (\file_exists($repo->getPath())) {
        deleteDir($repo->getPath());
    }

    return $repo;
})()]);

test('initialize repo', function (Repository $repo): void {
    t\assertDirectoryDoesNotExist($repo->getPath());
    t\assertDirectoryDoesNotExist($repo->getGitPath());
    $repo->initialize();
    t\assertDirectoryExists($repo->getPath());
    t\assertDirectoryExists($repo->getGitPath());
})->with('repo');

test('repo directory does not exists with debug false', function (): void {
    $repo = new Repository(__DIR__.'/build/repo', debug: false);
    t\assertNull($repo->getBranch());
});

test('repo head does not exists with debug true', function (Repository $repo): void {
    $repo->getBranch();
})->with('repo')->throws(ProcessFailedException::class);

test('repo has access to system,local, and local config', function (Repository $repo): void {
    t\assertNotEmpty($repo->getConfig());
    t\assertFalse($repo->getConfig('core.bare', false));
})->with('repo');

test('repo head exits but has no commits', function (Repository $repo): void {
    $repo->getLastCommit(); // your current branch 'master' does not have any commits yet
})->with('repo')->throws(ProcessFailedException::class);

test('repo by adding three commits', function (Repository $repo): void {
    \file_put_contents($repo->getPath().'/textA.txt', 'A file containing some text labeled A.');
    $repo->commit(new Message('Initial commit'));
    \file_put_contents($repo->getPath().'/textB.txt', 'A file containing some text labeled B.');
    $repo->commit(new Message('Second commit'));
    \file_put_contents($repo->getPath().'/textC.txt', 'A file containing some text labeled C.');
    $repo->commit(new Message('Third commit', 'This commit has a body message'));

    t\assertCount(3, $repo->getLog()->getCommits());
    $commit = $repo->getLastCommit();

    t\assertSame('Divine Niiquaye Ibok', $commit->getAuthor()->getName());
    t\assertSame('divineibok@gmail.com', $commit->getAuthor()->getEmail());
    t\assertSame('Divine Niiquaye Ibok', $commit->getCommitter()->getName());
    t\assertSame('divineibok@gmail.com', $commit->getCommitter()->getEmail());
    t\assertSame($commit->getCommitter()->getDate()->getTimestamp(), $commit->getAuthor()->getDate()->getTimestamp());
    t\assertSame('Third commit', $commit->getMessage()->getSubject());
    t\assertSame('This commit has a body message', $commit->getMessage()->getBody());
    t\assertSame(['textA.txt', 'textB.txt', 'textC.txt'], \array_keys($commit->getTree()->getEntries()));
})->with('repo');
