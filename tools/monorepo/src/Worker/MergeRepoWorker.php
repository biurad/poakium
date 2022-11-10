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

use Biurad\Monorepo\{Monorepo, PriorityInterface, WorkerInterface, WorkflowCommand};
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A workflow worker to merge a repository into the main repository.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class MergeRepoWorker implements PriorityInterface, WorkerInterface
{
    private function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Running non-existing repositories merge';
    }

    /**
     * {@inheritdoc}
     */
    public static function configure(WorkflowCommand $command): self
    {
        $command->addOption('read-tree', null, InputOption::VALUE_NONE, 'Force use read-tree merge even if filter-repo command exist');

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function work(Monorepo $repo, InputInterface $input, SymfonyStyle $output): int
    {
        $mainRepo = $repo->getRepository();
        $output->writeln('<info>Checking git filter-repo command existence...</info>');

        if ($readTree = $input->getOption('read-tree')) {
            $output->writeln('<warning>Using read-tree merge is dangerous and may break your repository.</warning>');

            if (!$input->getOption('quiet') && !$output->confirm('Do you want to continue?', false)) {
                return $mainRepo->getExitCode();
            }
        } elseif (!$mainRepo->check('filter-repo', ['-h'])) {
            $output->writeln('Visit <comment>https://github.com/newren/git-filter-repo</comment> and install git-filter-repo.');

            return WorkflowCommand::FAILURE;
        }

        if ($mainRepo->check('status', ['--porcelain'])) {
            $output->writeln('<error>Git status shows pending changes in repo</error>');

            return WorkflowCommand::FAILURE;
        }

        return $repo->resolveRepository($output, static function (array $required) use ($output, $mainRepo, $readTree): int {
            [$url, $remote, $path, $clonePath] = $required;
            $output->writeln(\sprintf('<info>Rewriting %s commit history to point to %s</info>', $url, $path));

            if (!$readTree) {
                $mainRepo->run('filter-repo', ['--force', '--to-subdirectory-filter', $path], null, $clonePath);
            }

            $getRemoteBranches = $mainRepo->runConcurrent([
                ['config', '--add', "remote.{$remote}.fetch", "+refs/tags/*:refs/tags/{$remote}/*"],
                ['config', "remote.{$remote}.tagOpt", '--no-tags'],
                ['fetch', '--all'],
                ['for-each-ref', '--format="%(refname:lstrip=3)"', "refs/remotes/{$remote}"],
            ])[3] ?? '';

            foreach (\explode("\n", $getRemoteBranches) as $branch) {
                if (empty($branch = \trim($branch, '"'))) {
                    continue;
                }
                $hasBranch = $mainRepo->check('show-ref', ['--verify', "refs/heads/{$branch}"]);
                $mergeMsg = "Merge branch '{$remote}/{$branch}' into {$branch}";

                if ($readTree) {
                    $commands = [
                        ['checkout', '-b', $hasBranch ? $branch.($i = \uniqid('-')) : $branch, "{$remote}/{$branch}"],
                        ['rm', '-rf', '*'],
                        ['read-tree', '--prefix', $path.'/', "{$remote}/{$branch}"],
                        ['commit', '-m', "Added remote-tracking {$remote}/{$branch}", '--allow-empty'],
                        ['reset', '--quiet', '--hard'],
                    ];

                    if ($hasBranch) {
                        $commands[] = ['checkout', $branch];
                        $commands[] = ['merge', '--allow-unrelated-histories', $branch.$i, '-m', $mergeMsg];
                        $commands[] = ['branch', '-D', $branch.$i];
                    }
                } else {
                    $commands = [
                        ['checkout', '--quiet', "{$remote}/{$branch}"],
                        ['switch', '--quiet', ...($hasBranch ? [$branch] : ['-c', $branch])],
                        ['merge', "{$remote}/{$branch}", '--allow-unrelated-histories', '--no-edit', '--no-verify', '--quiet', '-m', $mergeMsg],
                    ];
                }

                $mainRepo->runConcurrent($commands);
            }

            if (0 === $merged = $mainRepo->getExitCode()) {
                $output->writeln(\sprintf('Merged "%s" into <info>%s/%s</info>', $url, $mainRepo->getPath(), \ltrim($path, '/')));
            }

            return $merged;
        }, static fn (array $v): bool => !$v[3]);
    }
}
