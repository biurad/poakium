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

namespace Biurad\Monorepo\Worker;

use Biurad\Monorepo\{Monorepo, WorkerInterface, WorkflowCommand};
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Process\Process;

/**
 * A workflow worker for splitting commits to repositories.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class SplitCommitsWorker implements WorkerInterface
{
    private function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Running repositories commits splitting';
    }

    /**
     * {@inheritdoc}
     */
    public static function configure(WorkflowCommand $command): self
    {
        $multi = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL;
        $command->addOption('branch', 'b', $multi, 'Defaults to all branches that match the configured branch filter. (also accepts -b "*")', []);
        $command->addOption('no-branch', null, InputOption::VALUE_NONE, 'If set, no branches will be pushed.');

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function work(Monorepo $repo, InputInterface $input, SymfonyStyle $output): int
    {
        [$mainRepo, $branches] = [$repo->getRepository(), $input->getOption('branch')];
        $currentBranch = $mainRepo->getBranch()->getName();

        if (!\is_executable($split = __DIR__.'/../../bin/splitsh-lite')) {
            $mainRepo->run('update-index', ['--chmod=+x']);
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $output->error([
                'The splitsh-lite command used to split commits to repositories',
                'is currently not supported on Windows.',
                'Kindly use Windows Subsystem for Linux (WSL) to run this command.',
                'Support for Windows is being worked on and will be available soon.',
            ]);

            return WorkflowCommand::FAILURE;
        }

        if ($branches && '*' === $branches[0]) {
            if (\count($branches) > 1) {
                $output->writeln(\sprintf('<error>Expected "*" as the only value for option "--branch", got "%s"</error>', \implode(', ', $branches)));

                return WorkflowCommand::FAILURE;
            }
            [$branches, $allBranches] = [[], true];
        }

        foreach ($mainRepo->getBranches() as $branch) {
            if (isset($allBranches) || (1 === \preg_match($repo->config['branch_filter'], $branch->getName()) && !$input->getOption('no-branch'))) {
                $branches[] = $branch->isRemote() ? \substr($branch->getName(), 7) : $branch->getName();
            }
        }

        return $repo->resolveRepository(
            $output,
            static function (array $required) use ($input, $output, $currentBranch, $branches, $split, $repo, $mainRepo): int {
                [$url, $remote, $path, $clonePath] = $required;

                if (!\file_exists($mainRepo->getPath()."/$path")) {
                    throw new InvalidOptionsException(\sprintf('The repo for "%s" path "%s" does not exist.', $remote, $path));
                }

                foreach (\array_unique($branches) as $branch) {
                    $output->writeln(\sprintf('<info>Splitting commits from branch %s into %s</info>', $branch, $url));
                    $verify = ['-1', '--format=%ad | %s [%an]', '--date=short'];
                    $pushChanges = [];

                    ($s = Process::fromShellCommandline(
                        "{$split} --prefix={$path} --origin=origin/{$branch} --target=".$target = "refs/splits/$remote",
                        $mainRepo->getPath(),
                        timeout: 1200
                    ))->run();

                    if ($output->isVerbose()) {
                        $output->writeln(\sprintf('<%s>[debug] Command "%s": %s</%1$s>', $s->isSuccessful() ? 'info' : 'error', $s->getCommandLine(), $s->getErrorOutput()));

                        if (!$s->isSuccessful()) {
                            continue;
                        }
                    }

                    if ($mainRepo->run('log', [$branch, ...$verify], cwd: $clonePath) !== $mainRepo->run('log', [$target, ...$verify])) {
                        $count = (int) \rtrim($mainRepo->run('rev-list', ['--count', $target]) ?? '0');
                        $updates = (int) \rtrim($mainRepo->run('rev-list', ['--count', $branch], cwd: $clonePath) ?? '0');

                        if (($count = $updates > $count ? $updates - $count : $count - $updates) < 0) {
                            continue;
                        }

                        $output->writeln(\sprintf('<info>Pushing (%d) commits from branch %s to %s</info>', $count, $branch, $url));
                        $mainRepo->runConcurrent(0 === $updates ? [
                            ['push', $input->getOption('force') ? '-f' : '-q', $remote, "+$target:refs/heads/$branch"],
                        ] : [
                            ['checkout', '--orphan', "split-$remote"],
                            ['reset', '--hard'],
                            ['pull', $remote, $branch],
                            ['cherry-pick', ...\explode(' ', "$target~".\implode(" $target~", \array_reverse(\range(0, $count - 1))))],
                            ['push', $input->getOption('force') ? '-f' : '-q', $remote, "+refs/heads/split-$remote:$branch"],
                            ['checkout', $currentBranch],
                        ]);

                        foreach ($mainRepo->getLog(0 === $updates ? "refs/splits/$target" : "split-$remote", limit: $count)->getCommits() as $commit) {
                            if (!$tag = \rtrim($mainRepo->run('tag', ['--points-at', (string) $commit]) ?? '')) {
                                continue;
                            }

                            if (\str_starts_with($tag, $remote.'/')) {
                                $tag = \substr($tag, \strlen($remote.'/'));
                            }

                            if ($mainRepo->check('tag', ['--points-at', $tag], cwd: $clonePath)) {
                                $output->writeln(\sprintf('<info>Tag %s/%s in branch "%s" exists, skipping</info>', $remote, $tag, $branch));
                                continue;
                            }

                            if ($branch !== $rBranch = \rtrim($mainRepo->run('branch', ['--show-current'], cwd: $clonePath))) {
                                $mainRepo->run('checkout', [$rBranch], cwd: $clonePath);
                            }

                            if (true !== $verify) {
                                $verify = true;
                            }

                            $output->writeln(\sprintf('<info>Creating tag %s/%s in branch "%s"</info>', $remote, $tag, $branch));
                            $mainRepo->setEnvVars($mainRepo->getEnvVars() + [
                                'GIT_COMMITTER_DATE' => $commit->getCommitter()->getDate()->format('D, d M Y H:i:s'),
                                'GIT_AUTHOR_DATE' => $commit->getAuthor()->getDate()->format('D, d M Y H:i:s'),
                            ]);
                            $mainRepo->run('tag', [$tag, (string) $commit, '-m', "Release of $tag"], cwd: $clonePath);
                            $mainRepo->removeEnvVars('GIT_AUTHOR_DATE', 'GIT_COMMITTER_DATE');
                        }

                        if (!$input->getOption('no-push')) {
                            $pushChanges[] = ['push', ...($input->getOption('force') ? ['-f', '-u'] : ['-u']), 'origin', "$branch:$branch"];

                            if (true === $verify) {
                                $pushChanges[] = [...\end($pushChanges), '--tags']; // Re-push to update tags only
                            }
                        }

                        $mainRepo->runConcurrent([['update-ref', '-d', $target], ...($updates > 0 ? [['branch', '-D', "split-$remote"]] : [])]);
                    } else {
                        $output->writeln(\sprintf('<info>Nothing to commit; On branch %s, "%s/%1$s" is up to date</info>', $branch, $remote));
                    }
                }

                if (!empty($pushChanges)) {
                    $output->writeln(\sprintf('<info>Preparing to push changes to %s</info>', $url));
                    $mainRepo->runConcurrent($pushChanges, cwd: $clonePath);
                }

                return $mainRepo->getExitCode();
            }
        );

        return $mainRepo->getExitCode();
    }
}
