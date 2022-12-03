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

use Biurad\Git\Repository;
use Biurad\Monorepo\{Monorepo, WorkerInterface, WorkflowCommand};
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Process\{ExecutableFinder, Process};

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
        $command->addOption('release', 't', InputOption::VALUE_OPTIONAL, 'Release of new tag (accepted pattern: <tag>[=<branch>])');

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function work(Monorepo $repo, InputInterface $input, SymfonyStyle $output): int
    {
        [$finder, $mainRepo, $branches] = [new ExecutableFinder(), $repo->getRepository(), $input->getOption('branch')];
        $currentBranch = $mainRepo->getBranch()->getName();

        if (null === $split = $finder->find('splitsh-lite', null, [__DIR__.'/../../bin'])) {
            $output->writeln('<error>splitsh-lite not found</error>');

            return WorkflowCommand::FAILURE;
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

                    if ($mainRepo->run('log', ['master', '-1', '--format=%s'], null, $clonePath) !== $mainRepo->run('log', [$target, '-1', '--format=%s'])) {
                        $updates = $mainRepo->run('rev-list', ['--count', $target]);
                        $count = $mainRepo->run('rev-list', ['--count', $branch], null, $clonePath) ?? 0;

                        $output->writeln(\sprintf('<info>Pushing (%d) commits from branch %s to %s</info>', $updates - $count, $branch, $url));
                        $mainRepo->runConcurrent(0 === $count ? [
                            ['push', $input->getOption('force') ? '-f' : '-q', $remote, "+$target:refs/heads/$branch"],
                            ['update-ref', '-d', $target],
                        ] : [
                            ['checkout', '--orphan', "split-$remote"],
                            ['reset', '--hard'],
                            ['pull', $remote, $branch],
                            ['cherry-pick', "$target~".\implode(" $target~", \array_reverse(\range(0, $updates - $count - 1)))],
                            ['push', $input->getOption('force') ? '-f' : '-q', $remote, "+refs/heads/split-$remote:$branch"],
                            ['checkout', $currentBranch],
                            ['branch', '-D', "split-$remote"],
                            ['update-ref', '-d', $target],
                        ]);

                        if (!$input->getOption('no-push')) {
                            $pushChanges[] = ['push', ...($input->getOption('force') ? ['-f', '-u'] : ['-u']), 'origin', "$branch:$branch"];
                        }
                    } else {
                        $output->writeln(\sprintf('<info>Nothing to commit; On branch %s, "%s/%1$s" is up to date</info>', $branch, $remote));
                    }
                }

                if ($tagged = $input->getOption('release')) {
                    [$tagged, $repo] = [\explode('=', $tagged, 2), new Repository($clonePath, [], $repo->isDebug(), $repo->getLogger())];

                    if (!$repo->getBranch($rBranch = $tagged[1] ?? $currentBranch)) {
                        $output->writeln(\sprintf('<error>Release Branch %s does not exist</error>', $rBranch));
                    } else {
                        if ($repo->getBranch()->getName() !== $rBranch) {
                            $repo->run('checkout', [$rBranch]);
                        }

                        $tags = '*' === $tagged[0] ? \explode("\n", $mainRepo->run('tag', ['--list', '--points-at', $rBranch]) ?? '') : [$tagged[0]];
                        $tagPushes = [];

                        if ($repo->getBranch()->getName() !== $rBranch) {
                            $tagPushes[] = ['checkout', $rBranch];
                        }

                        foreach (\array_filter($tags) as $tag) {
                            if (\str_starts_with($tag, $remote.'/')) {
                                $tag = \substr($tag, \strlen($remote.'/'));
                            }

                            if (!$repo->getTag($tag)) {
                                $output->writeln(\sprintf('<info>Creating tag %s for repo %s</info>', $tagged[0], $remote));
                                $tagPushes[] = ['tag', $tagged[0], '-m', 'Release '.$tagged[0]];
                                $tagPushes[] = ['push', ...($input->getOption('force') ? ['origin', '--tags', '-f'] : ['origin', '--tags']), $rBranch];

                                if (!$input->getOption('no-push')) {
                                    $pushChanges[] = \end($tagPushes);
                                }
                            }
                        }

                        $repo->runConcurrent($tagPushes);
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
