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
use Biurad\Git\{CommitNew, Repository};
use PHPUnit\Framework as t;
use PHPUnit\Util\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

if (!\function_exists('deleteDir')) {
    function deleteDir(string $dir): void
    {
        Process::fromShellCommandline(('\\' === \DIRECTORY_SEPARATOR ? 'rd /s /q "' : 'rm -rf "').$dir.'"')->mustRun();
    }
}

dataset('repo', [(static function (): Repository {
    $repo = new Repository(__DIR__.'/build/repo', [], debug: false);

    if (\file_exists($repo->getPath())) {
        deleteDir($repo->getPath());
    }

    return $repo;
})()]);

dataset('cloned', [(static function (): Repository {
    $repo = new Repository(__DIR__.'/build/cloned', debug: false);

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
    (new Repository($repo->getPath()))->getBranch();
})->with('repo')->throws(ProcessFailedException::class);

test('repo has access to system,local, and local config', function (Repository $repo): void {
    t\assertNotEmpty($repo->getConfig());
    t\assertFalse($repo->getConfig('core.bare', false));
})->with('repo');

test('repo has no references, commits, branches, or tags', function (Repository $repo): void {
    t\assertEmpty($repo->getReferences());
    t\assertNull($repo->getLastCommit());
    t\assertEmpty($repo->getBranches());
    t\assertEmpty($repo->getTags());
    t\assertEmpty($repo->getLog()->getCommits());
})->with('repo');

test('repo has invalid commit data', function (Repository $repo): void {
    $repo->getCommit('99172034745672ec09618948f2e640cb46421bbf')->getMessage();
})->with('repo')->throws(RuntimeException::class, 'Failed to get commit data for "99172034745672ec09618948f2e640cb46421bbf"');

test('repo add a commit with modified author date', function (Repository $repo): void {
    \file_put_contents($repo->getPath().'/test.txt', 'test');
    $repo->commit(new CommitNew(
        new Message('First commit'),
        [],
        (clone $repo->getAuthor())->setDate($d = new \DateTimeImmutable('2022-11-15 00:00:00')),
        $repo->getAuthor()
    ));
    t\assertEquals($d->format('U'), $a = $repo->getLastCommit()->getAuthor()->getDate()->format('U'));
    t\assertNotEquals($a, $repo->getLastCommit()->getCommitter()->getDate()->format('U'));
})->with('repo');

test('repo by adding three commits', function (Repository $repo): void {
    \file_put_contents($repo->getPath().'/textA.txt', 'A file containing some text labeled A.');
    $repo->commit(new Message('Second commit'));
    \file_put_contents($repo->getPath().'/textB.txt', 'A file containing some text labeled B.');
    $repo->commit(new Message('Third commit'));
    \file_put_contents($repo->getPath().'/textC.txt', 'A file containing some text labeled C.');
    $repo->commit(new Message('Fourth commit', 'This commit has a body message'));

    t\assertCount(4, $repo->getLog()->getCommits());
    $commit = $repo->getLastCommit();

    t\assertSame($commit->getAuthor()->getName(), $commit->getCommitter()->getName());
    t\assertSame($commit->getAuthor()->getEmail(), $commit->getCommitter()->getEmail());
    t\assertSame($commit->getCommitter()->getDate()->getTimestamp(), $commit->getAuthor()->getDate()->getTimestamp());
    t\assertSame('Fourth commit', $commit->getMessage()->getSubject());
    t\assertSame('This commit has a body message', $commit->getMessage()->getBody());
    t\assertSame(['test.txt', 'textA.txt', 'textB.txt', 'textC.txt'], \array_keys($commit->getTree()->getEntries()));
})->with('repo');

test('repo branch is created and exists in references', function (Repository $repo): void {
    $repo->run('branch', [$name = $repo->getConfig('init.defaultbranch', 'master')]);
    t\assertSame($name, ( $branch = $repo->getBranch($name))->getName());
    t\assertSame($branch, $repo->getBranch());
    t\assertSame($repo->getLastCommit(), $branch->getCommit());
    t\assertSame('refs/heads/'.$name, $branch->getRevision());
    t\assertNull($branch->getRemoteName());
    t\assertTrue($branch->isLocal());
    t\assertFalse($branch->isRemote());
})->with('repo');

test('repo branch cannot find commit hash', function (Repository $repo): void {
    (string) (new Biurad\Git\Branch($repo, 'refs/heads/invalid'));
})->with('repo')->throws(InvalidArgumentException::class, 'Invalid commit hash. Empty hash provided');

test('repo revision has invalid ref name', function (Repository $repo): void {
    (new Biurad\Git\Revision($repo, 'error'))->getRevision();
})->with('repo')->throws(
    RuntimeException::class,
    'Invalid revision provided "error", expected revision to start with a "refs/"'
);

test('repo commit with sub-directory including files to test commit sub-tree', function (Repository $repo): void {
    Filesystem::createDirectory($dir = $repo->getPath().'/subdir');
    \file_put_contents($dir.'/a.txt', 'A text file with contents labeled "A".');
    \file_put_contents($dir.'/b.txt', 'A text file with contents labeled "B".');
    \file_put_contents($dir.'/c.txt', 'A text file with contents labeled "C".');
    t\assertEquals([
        'subdir/a.txt',
        'subdir/b.txt',
        'subdir/c.txt',
    ], \array_map(fn (array $v): string => $v['file'], $repo->getUntrackedFiles()));
    $repo->commit(new CommitNew(new Message('Added a `subdir` folder with text files'), [$dir]));
    t\assertCount(5, ($tree = $repo->getLastCommit()->getTree())->getEntries());
    t\assertTrue($tree->has('subdir'));
    t\assertIsInt($tree->get('subdir')->getMode());
    t\assertCount(3, $tree->getSubTree('subdir')->getEntries());
    t\assertInstanceOf(Biurad\GiT\Commit\Tree::class, $tree->getSubTree('subdir'));
    t\assertInstanceOf(Biurad\GiT\Commit\Blob::class, $tree->getSubTree('subdir/a.txt'));
})->with('repo');

test('repo total size in kilobytes', function (Repository $repo): void {
    t\assertEquals('\\' === \DIRECTORY_SEPARATOR ? 3 : 0, $repo->getSize());
    t\assertGreaterThan(20, $repo->getSize(true)); // greater than 20KB
})->with('repo');

test('repo commit to update a file and test log on default branch', function (Repository $repo): void {
    \file_put_contents($repo->getPath().'/test.txt', "\n\n3 text files added into sub-directory \"subdir\"");
    $repo->commit(new Message('Updated the test.txt file'), args: ['--no-gpg-sign']);
    t\assertCount(6, $repo->getLog($b = $repo->getConfig('init.defaultbranch', 'master')));
    t\assertCount(2, $l1 = $repo->getLog($b, 'test.txt'));
    t\assertSame([$b], $l1->getRevisions());
    t\assertSame(['test.txt'], $l1->getPaths());
    t\assertSame($repo->getLastCommit(), $l1->getIterator()[0]);
})->with('repo');

test('repo log offset from 0 with limit of 1', function (Repository $repo): void {
    $log = new Biurad\Git\Log($repo);
    $log->setOffset(0);
    $log->setLimit(1);
    t\assertSame($repo->getLastCommit(), $log->getCommits()[0]);
})->with('repo');

test('repo cloned into another repo which will serve as remote', function (Repository $a, Repository $b): void {
    Filesystem::createDirectory($b->getPath());
    $b->run('clone', [$a->getPath(), '.']);
    t\assertSame(\array_map('strval', $a->getLog()->getCommits()), \array_map('strval', $b->getLog()->getCommits()));
    t\assertNotEmpty($b->getRemotes());
})->with('repo', 'cloned');

test('repo has tag on last commit and test all references', function (Repository $repo): void {
    $repo->run('tag', ['1.0']);
    t\assertSame('1.0', $repo->getTag('1.0')->getName());
    t\assertSame($b = $repo->getConfig('init.defaultbranch', 'master'), $repo->getBranch($b)->getName());
    t\assertSame($repo->getTag(), $repo->getTag('1.0'));
    t\assertFalse($repo->getTag()->isAnnotated());
    t\assertCount(2, $repo->getBranches()); // one local and one remote
    t\assertCount(1, $repo->getTags());
    t\assertCount(4, $repo->getReferences());
    t\assertCount(6, $repo->getTag()->getCommits()); // All commits under 1.0 tag
    t\assertSame($repo->getTag('1.0')->getCommit(), $repo->getBranch($b)->getCommit());
})->with('cloned');

test('repo last commit and tag objects', function (Repository $repo): void {
    $tag = $repo->getTag('1.0');
    $commit = $tag->getCommit();
    $a = $commit->getAuthor();
    $c = $commit->getCommitter();
    t\assertSame($commit, $repo->getLastCommit());
    t\assertFalse($tag->isAnnotated());
    t\assertSame('refs/tags/1.0', $tag->getRevision());
    //t\assertFalse($commit->getSignature());
    t\assertSame(\substr((string) $commit, 0, 7), $commit->getShortHash());
    t\assertSame($a->getName(), $c->getName());
    t\assertSame($a->getDate()->getTimestamp(), $c->getDate()->getTimestamp());
    t\assertSame($a->getEmail(), $c->getEmail());
    t\assertCount(4, $commit->getReferences());
    t\assertNull($commit->getMessage()->getBody());
    t\assertCount(1, $commit->getParents());
})->with('cloned');

test('repo to checkout exists branch into a new "new-feature" branch', function (Repository $repo): void {
    t\assertTrue($repo->check('checkout', ['-b', 'new-feature']));
    $repo->reset();
    t\assertCount(2, $repo->getBranch()); // Since all commit hashes are the same
    t\assertSame('refs/heads/new-feature', $repo->getBranch()[1]->getRevision());
    t\assertCount(6, $repo->getLog('new-feature'));
})->with('cloned');

test('repo with remote & pushing changes to remote repo', function (Repository $a, Repository $b): void {
    $b->push(args: ['--all']);
    $a->reset();
    $b->reset();
    t\assertSame(\array_map('strval', $a->getLog()->getCommits()), \array_map('strval', $b->getLog()->getCommits()));
    t\assertCount(2, $a->getBranches());
    t\assertCount(1, $r = $b->getRemotes());
    t\assertSame('origin', (string) $r['origin']);
    t\assertSame($a->getPath(), $r['origin']->getFetchUrl());
    t\assertSame($a->getPath(), $r['origin']->getPushUrl());
})->with('repo', 'cloned');

test('repo pulls new commit from remote to', function (Repository $a, Repository $b): void {
    $b->runConcurrent([
        ['commit', '-m', 'New commit on new-feature branch', '--allow-empty'],
        ['push', 'origin', '--all'],
    ]);
    $a->run('checkout', ['new-feature']);
    $b->reset();
    $a->reset();
    t\assertSame($a->getBranch()->getRevision(), $b->getBranch()->getRevision());
    t\assertSame(\array_map('strval', $a->getLog()->getCommits()), \array_map('strval', $b->getLog()->getCommits()));
    t\assertInstanceOf(Biurad\Git\Branch::class, $a->getBranch());
    t\assertSame('refs/heads/new-feature', $a->getBranch()->getRevision());
})->with('repo', 'cloned');

test('repo log difference between default branch and new-feature branch', function (Repository $repo): void {
    $b = $repo->getConfig('init.defaultbranch', 'master');
    t\assertCount(1, $repo->getLog($b.'...new-feature'));
    t\assertCount(1, $repo->getLog('new-feature...'.$b));
})->with('repo');

test('repo merge commits from new-feature branch into the default branch and delete the branch', function (Repository $repo): void {
    t\assertTrue($repo->check('checkout', [$repo->getConfig('init.defaultbranch', 'master')]));
    $repo->run('merge', ['new-feature']);
    $repo->run('branch', ['-d', 'new-feature']);
    t\assertCount(7, $repo->getLog());
    t\assertNull($repo->getBranch('new-feature'));
})->with('repo');
