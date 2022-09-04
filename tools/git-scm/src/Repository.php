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

namespace Biurad\Git;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * The Git Repository.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Repository
{
    private float $timeout = 600.0;
    private string $command = 'git';
    private int $exitCode = 0;
    private string $gitDir;
    private array $cache = [];

    public function __construct(
        private string $path,
        private array $envVars,
        private bool $debug = true,
        private ?LoggerInterface $logger = null,
    ) {
        $this->gitDir = $this->path.'/.git';

        if (\defined('PHP_WINDOWS_VERSION_BUILD') && isset($_SERVER['PATH']) && !isset($envVars['PATH'])) {
            $this->envVars['PATH'] = $_SERVER['PATH'];
        }
    }

    /**
     * Create a local clone of a remote repository.
     *
     * @param string            $path    The path to clone the repository to
     * @param array<int,string> $envVars
     * @param array<int,string> $args    Additional arguments to pass to the clone command
     */
    public static function fromRemote(
        string $remoteRepo,
        string $path,
        array $envVars = [],
        array $args = [],
        bool $debug = true,
        LoggerInterface $logger = null,
    ): self {
        if (!\file_exists($path)) {
            \mkdir($path, 0777, true);
        }

        $repo = new self($path, $envVars, $debug, $logger);
        $repo->run('clone', [$remoteRepo, '.', ...$args], null, $path);

        return $repo;
    }

    /**
     * Set how long to wait for a command to finish.
     */
    public function setTimeOut(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Set the git command to use (e.g. 'git' or '/usr/bin/git').
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * Returns the url or path to the repository.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the path to the .git directory.
     */
    public function getGitPath(): string
    {
        return $this->isBare() ? $this->path : $this->gitDir;
    }

    /**
     * Returns true if the repository is bare.
     */
    public function isBare(): bool
    {
        if (!\file_exists($this->path)) {
            return false;
        }

        return 'true' === \trim($this->run('rev-parse', ['--is-bare-repository']));
    }

    /**
     * @return int the size of repository in kilobytes, 0 if command failed
     */
    public function getSize(): int
    {
        $o = $this->run('count-objects');

        if (empty($o) || 0 !== $this->exitCode) {
            return 0;
        }

        return (int) \substr($o, \strpos($o, ',') + 2, -10);
    }

    /**
     * Get the git repository configuration(s).
     */
    public function getConfig(string $key = null, string|int|float|bool $default = null): mixed
    {
        $resolve = fn (string $v) => 'true' === $v ? true : ('false' === $v ? false : (\is_numeric($v) ? (int) $v : $v));

        if (null === $key) {
            $o = $this->run('config', ['--list']);

            if (empty($o) || 0 !== $this->exitCode) {
                return null;
            }

            if (!isset($this->cache[$i = \md5($o)])) {
                foreach (\explode("\n", $o) as $line) {
                    if (!empty($line)) {
                        [$k, $v] = \explode('=', $line, 2);
                        $this->cache[$i][$k] = $resolve($v);
                    }
                }
            }

            return $this->cache[$i];
        }

        try {
            $o = $this->run('config', ['--get', $key]);
        } catch (ExceptionInterface) {
        }

        return (empty($o) || 0 !== $this->exitCode) ? $default : $resolve(\trim($o));
    }

    /**
     * Returns all remotes.
     *
     * @return array<string,string> [remote => url]
     */
    public function getRemotes(): array
    {
        $o = $this->run('remote', ['--verbose']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode('\n', $o) as $line) {
                [$remote, $url] = \explode('  ', $line, 2);

                if (\str_ends_with($url, '(push)')) {
                    continue;
                }
                $this->cache[$i][$remote] = \substr($url, 0, -7);
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns untracked files in the repository.
     *
     * @return array<int,array<string,string>> array of [status => '??', file => 'file.txt']
     */
    public function getUntrackedFiles(bool $staged = false): array
    {
        $o = $this->run('status', ['--porcelain', '-uall', $staged ? '-s' : '--empty-directory']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (!empty($line)) {
                    $status = \strrpos($line, "\0") + 1;
                    $this->cache[$i][] = ['status' => \substr($line, 0, $status), 'file' => \substr($line, $status)];
                }
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns an array of all branches.
     *
     * @return array<int,Branch>
     */
    public function getBranches(): array
    {
        $o = $this->run('branch', ['-a', '--format', '"%(refname:short) %(objectname)"']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (!empty($line)) {
                    [$branch, $hash] = \explode(' ', $line, 2);

                    if (\in_array($branch, ['refs/heads/HEAD', 'origin/HEAD'], true)) {
                        continue;
                    }

                    $ref = \str_starts_with($branch, 'origin/') ? 'remotes/' : 'heads/';
                    $this->cache[$i][] = new Branch($this, 'refs/'.$ref.$branch, $hash);
                }
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns branch(es) belonging to a revision, if count is 1, the first branch is returned.
     * Head is detached if the revision value is HEAD and empty.
     *
     * @param string $revision The branch ref name or short name, tag, or a commit hash
     *
     * @return array<int,Branch>|Branch
     */
    public function getBranch(string $revision = 'HEAD'): Branch|array
    {
        $o = $this->run('branch', ['--points-at', $revision, '--format', '"%(refname:short) %(objectname)"']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (!empty($line)) {
                    [$branch, $hash] = \explode(' ', $line, 2);

                    if (\in_array($branch, ['refs/heads/HEAD', 'origin/HEAD'], true)) {
                        continue;
                    }

                    if (\str_starts_with($revision, 'refs/')) {
                        $branch = (\str_starts_with($revision, 'refs/heads/') ? 'refs/heads/' : 'refs/remotes/').$branch;
                    } elseif (\str_starts_with($revision, 'heads/')) {
                        $branch = 'refs/heads/'.$branch;
                    } elseif (\str_starts_with($revision, 'origin/') || \str_starts_with($revision, 'remotes/')) {
                        $branch = 'refs/remotes/origin/'.$branch;
                    }

                    $this->cache[$i][] = new Branch($this, $branch, $hash);
                }
            }

            if (1 <= \count($branches = &$this->cache[$i] ?? [])) {
                $branches = $branches[0] ?? [];
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns an indexed array of all tags.
     *
     * @return array<int,Tag>
     */
    public function getTags(): array
    {
        $o = $this->run('tag', ['--sort=-creatordate', '--format', '"%(refname:short) %(objectname)"']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (!empty($line)) {
                    [$tag, $hash] = \explode(' ', $line, 2);
                    $this->cache[$i][] = new Tag($this, 'refs/tags/'.$tag, $hash);
                }
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns tag(s) belonging to a revision, if count is 1, the first tag is returned.
     *
     * @param string $revision The tag ref name or short name, tag, or a commit hash
     *
     * @return array<int,Tag>|Tag
     */
    public function getTag(string $revision = 'HEAD'): Tag|array
    {
        $o = $this->run('tag', ['--points-at', $revision, '--format', '"%(refname:short) %(objectname)"']);

        if (empty($o) || 0 !== $this->exitCode) {
            return [];
        }

        if (!isset($this->cache[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (!empty($line)) {
                    [$tag, $hash] = \explode(' ', $line, 2);
                    $this->cache[$i][] = new Tag($this, 'refs/tags/'.$tag, $hash);
                }
            }

            if (1 <= \count($tags = &$this->cache[$i] ?? [])) {
                $tags = $tags[0] ?? [];
            }
        }

        return $this->cache[$i];
    }

    /**
     * Returns a commit object from it given hash.
     */
    public function getCommit(string $commitHash): Commit
    {
        return $this->cache[$commitHash] ??= new Commit($this, $commitHash);
    }

    /**
     * Returns the current commit.
     */
    public function getLastCommit(): ?Commit
    {
        $o = $this->run('log', ['--pretty=format:%H', '-n', 1]);

        if (empty($o) || 0 !== $this->exitCode) {
            return null;
        }

        return $this->getCommit(\trim($o));
    }

    /**
     * @param null|array<int,string>|string $revisions a list of revisions or null if you want all history
     * @param null|array<int,string>|string $paths     paths to filter on
     * @param null|int                      $offset    start list from a given position
     * @param null|int                      $limit     limit number of fetched elements
     */
    public function getLog(array|string $revisions = null, array|string $paths = null, int $offset = null, int $limit = null): Log
    {
        return new Log($this, $revisions, $paths, $offset, $limit);
    }

    /**
     * Returns the global default git user.
     */
    public function getAuthor(): ?Commit\Identity
    {
        if (!isset($this->cache['author'])) {
            $data = $this->runConcurrent([
                ['config', '--get', '--global', 'user.name'],
                ['config', '--get', '--global', 'user.email'],
            ]);

            if (empty($data) || 0 !== $this->exitCode) {
                return null;
            }

            $this->cache['author'] = new Commit\Identity($data[0], $data[1]);
        }

        return $this->cache['author'];
    }

    /**
     * Returns the last exit code.
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Resets the repository object reference object, cache
     * and discard all local changes in your working directory.
     *
     * @param bool $keep preserve uncommitted local changes if true
     */
    public function reset(string $commitHash = null, bool $keep = false): void
    {
        $this->cache['author'] = null;
        $this->run('reset', [$keep ? '--keep' : '--hard', $commitHash ?? 'HEAD']);
    }

    /**
     * Cleans up the repository completely without leaving a single file.
     */
    public function clean(): void
    {
        $this->runConcurrent([
            ['clean', '-f', '-d', '-x'],
            ['rm', '-rfq', '--ignore-unmatch', '.'], // Extra to ensure everything is removed
            ['reset'],
        ]);
    }

    /**
     * Initializes the repository as a Git repository.
     */
    public function initialize(int|string ...$args): self
    {
        if (!\file_exists($this->getGitPath())) {
            $this->run('init', $args);
        }

        return $this;
    }

    /**
     * Adds a new commit to repository.
     *
     * @param array<int,string> $args arguments to pass to git commit
     */
    public function commit(CommitNew $commit, array $args = [], string $cwd = null): self
    {
        $commands = [];
        $msg = ($data = $commit->getData())[0]->__toString();
        $commands[] = !empty($paths = $data[1] ?? []) ? ['add', ...$paths] : ['add', '--all'];
        $oldEnv = $this->envVars;

        if (isset($data[2])) {
            $this->envVars['GIT_AUTHOR_NAME'] = $data[2]->getName();
            $this->envVars['GIT_AUTHOR_EMAIL'] = $data[2]->getEmail();

            if (null !== $d = $data[2]->getDate()) {
                $this->envVars['GIT_AUTHOR_DATE'] = $d->format('D, d M Y H:i:s');
            }

            if (!isset($data[3])) {
                $data[3] = $data[2];
            }
        }

        if (isset($data[3])) {
            $this->envVars['GIT_COMMITTER_NAME'] = $data[3]->getName();
            $this->envVars['GIT_COMMITTER_EMAIL'] = $data[3]->getEmail();

            if (null !== $d = $data[3]->getDate()) {
                $this->envVars['GIT_COMMITTER_DATE'] = $d->format('D, d M Y H:i:s');
            }
        }

        if (!empty($coAuthors = $data[4] ?? [])) {
            $coAuthors = \array_map(fn (Commit\Identity $i) => $i->getName().' <'.$i->getEmail().'>', $coAuthors);
            $data[1] .= "\n".($c = "\n".'Co-Authored-By: ').\implode($c, $coAuthors);
        }

        $msg = !empty($msg) ? ['-m', $msg.(!empty($data[1]) ? "\n".$data[1] : '')] : ['--allow-empty-message'];
        $commands[] = ['commit', '--allow-empty', ...$msg, ...$args];
        $this->runConcurrent($commands, cwd: $cwd);
        $this->envVars = $oldEnv;

        return $this;
    }

    /**
     * Amend a previous commit message from it hash.
     */
    public function amend(string $commitHash, Commit\Message $message, string $cwd = null): bool
    {
        $msg = $message->__toString();
        $msg = !empty($msg) ? ['-m', $msg] : ['--allow-empty-message'];

        return $this->check('commit', ['-C', $commitHash, ...$msg, '--amend'], null, $cwd);
    }

    /**
     * Pushes changes to a remote or git repository.
     *
     * @param array<int,string> $args arguments to pass to git push
     * @param null|string       $cwd  The working directory command should run from
     */
    public function push(string $remote = null, array $args = [], string $cwd = null): self
    {
        if (false === $this->getConfig('push.autoSetupRemote', false)) {
            if (0 === $this->getExitCode()) {
                $this->run('config', ['push.autoSetupRemote', 'true']);
            } elseif (!\in_array('--all', $args, true)) {
                \array_unshift($args, '--set-upstream', $remote ?? 'origin', $this->getBranch()[1]);
                $remote = null; // looks like git version is older than 2.35
            }
        }

        if (null !== $remote) {
            \array_unshift($args, $remote);
        }

        $this->run('push', $args, cwd: $cwd);

        return $this;
    }

    /**
     * Checks if command status is true or false.
     *
     * @param string                $command  Run git command eg. (checkout, branch, tag)
     * @param array<int,int|string> $args     Arguments of the git command
     * @param null|callable|string  $expected The string or callable to check command output
     * @param null|string           $cwd      The working directory command should run from
     */
    public function check(string $command, array $args = [], string|callable $expected = null, string $cwd = null): bool
    {
        try {
            $o = $this->run($command, $args, null, $cwd);
        } catch (ProcessFailedException) {
        }

        if (empty($o) || 0 !== $this->exitCode) {
            return false;
        }

        if (\is_string($expected)) {
            return $expected === $o;
        }

        return null === $expected ? true : $expected($o);
    }

    /**
     * @param string                $command  Run git command eg. (checkout, branch, tag)
     * @param array<int,int|string> $args     Arguments of the git command
     * @param null|callable         $callback Reads buffer, param of (string $type, string $buffer)
     * @param null|string           $cwd      The working directory command should run from
     *
     * @throws \RuntimeException while executing git command (debug-mode only)
     *
     * @return string output of a successful process or null if execution failed and debug-mode is disabled
     */
    public function run(string $command, array $args = [], callable $callback = null, string $cwd = null): ?string
    {
        $process = new Process([$this->command, $command, ...$args], $cwd ?? $this->path, $this->envVars, null, $this->timeout);

        try {
            $this->exitCode = $process->mustRun($callback)->getExitCode();

            return $process->getOutput();
        } catch (ExceptionInterface $e) {
            $this->logger?->error($e->getMessage());

            return $this->debug ? throw $e : null;
        } finally {
            if (null !== $this->logger) {
                $message = 'Command "%s" finished successfully';

                if ($this->debug) {
                    $message .= \sprintf(' at "%.2fms".', (\microtime(true) - $process->getStartTime()) * 1000);
                }
                $this->logger->debug(\sprintf($message, $process->getCommandLine()));
            }
        }
    }

    /**
     * Concurrently run multiple git commands.
     *
     * @param array<int,array<int,string>|string> $commands An array list of commands eg. [['log', 'master'], ['git', '-v']]
     * @param null|callable                       $callback Reads buffer, param of (array $command, string $type, string $buffer)
     * @param null|string                         $cwd      The working directory command should run from
     *
     * @return array<int,string>
     */
    public function runConcurrent(array $commands, callable $callback = null, bool $exitOnFailure = true, string $cwd = null): array
    {
        $outputs = [];

        if (null !== $this->logger) {
            $message = 'Concurrent command(s) [%s] finished successfully';
            $lines = [];

            if ($this->debug) {
                $start = 0;
            }
        }

        if ($hasCallback = null !== $callback) {
            $callback = fn (array $command): callable => fn (string $type, string $buffer) => $callback($type, $buffer, $command);
        }

        foreach ($commands as $k => $command) {
            $command = \is_array($command) ? $command : [$command];
            $process = new Process([$this->command, ...$command], $cwd ?? $this->path, $this->envVars, null, $this->timeout);

            try {
                $process->start($hasCallback ? $callback($command) : null);

                foreach ($process->getIterator() as $buffer) {
                    if (!isset($outputs[$k])) {
                        $outputs[$k] = $buffer;
                        continue;
                    }
                    $outputs[$k] .= "\n" === $buffer[0] ? $buffer : "\n".$buffer;
                }

                $this->exitCode = $process->wait();
            } catch (ExceptionInterface $e) {
            }

            if (!$process->isSuccessful()) {
                $this->logger?->error(isset($e) ? $e->getMessage() : \sprintf('Command "%s" failed', $process->getCommandLine()));

                if ($exitOnFailure) {
                    return $this->debug ? throw ($e ?? new ProcessFailedException($process)) : [];
                }
            }

            if (isset($lines)) {
                $lines[] = $process->getCommandLine();

                if (isset($start)) {
                    $start += \microtime(true) - $process->getStartTime();
                }
            }
        }

        if (isset($message)) {
            if (isset($start) && $start > 0) {
                $message .= \sprintf(' at "%.2fms".', $start * 1000);
            }
            $this->logger->debug(\sprintf($message, \implode(', ', $lines ?? [])));
        }

        return $outputs;
    }

    /**
     * Executes a shell command on the repository, using PHP pipes.
     *
     * @param string $command The command to execute
     */
    public function shell(string $command, array $env = []): void
    {
        $argument = \sprintf('%s \'%s\'', $command, $this->getGitPath());
        $prefix = '';

        foreach ($env as $name => $value) {
            $prefix .= \sprintf('export %s=%s;', \escapeshellarg($name), \escapeshellarg($value));
        }

        \proc_open($prefix.'git shell -c '.\escapeshellarg($argument), [\STDIN, \STDOUT, \STDERR], $pipes);
    }
}