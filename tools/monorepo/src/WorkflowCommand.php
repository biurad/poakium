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

namespace Biurad\Monorepo;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The Monorepo's workflow command.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class WorkflowCommand extends Command
{
    private Monorepo $monorepo;
    private SymfonyStyle $output;

    /** @var array<int,WorkerInterface> */
    private array $workers = [];

    public function __construct(private string $rootPath)
    {
        parent::__construct('workflow');
        $this->ignoreValidationErrors();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (0 === $totalWorkerCount = \count($jobWorkers = $this->workers)) {
            return self::FAILURE;
        }

        $isDryRun = (bool) $input->getOption('dry-run');
        $stage = $input->getArgument('job');
        $status = self::SUCCESS;

        foreach ($jobWorkers as $i => $jobWorker) {
            $this->output->newLine();
            $this->output->title(\sprintf('%s/%d) ', ++$i, $totalWorkerCount).$jobWorker->getDescription());

            if ($this->output->isVerbose()) {
                $output->writeln(\sprintf('class: %s; stage: %s;', $jobWorker::class, $stage));
                $this->output->newLine();
            }

            if (!$isDryRun && self::SUCCESS !== $status = $jobWorker->work($this->monorepo, $input, $this->output)) {
                break;
            }
        }

        if (self::SUCCESS !== $status) {
            $this->output->error(\sprintf('Workflow job "%s" failed with exit code %d', $stage, $status));
        } elseif ($isDryRun) {
            $this->output->note(\sprintf('Workflow job "%s" running in dry mode, nothing is changed', $stage));
        } else {
            $this->output->success(\sprintf('Workflow job "%s" is now completed and successful!', $stage));
        }

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Workflow job runner for working the monorepo repositories');
        $this->addArgument('job', InputArgument::OPTIONAL, 'The specified workflow job to run', 'main');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the workflow job to run');
        $this->addOption('no-push', null, InputOption::VALUE_NONE, 'Do not push to the remote repositories.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not perform operations, just their preview');
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Absolute path to project\'s directory, defaults to directory were script is called.');
        $this->addOption('cache', 'c', InputOption::VALUE_OPTIONAL, 'Absolute path to cache directory, defaults to .monorepo-cache in the project directory.');
        $this->addOption('clean', 'x', InputOption::VALUE_NONE, 'Re-clone cached repositories of monorepo\'s sub-repo');
        $this->addOption('only', 'o', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of sub-repos specified by repo name', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $repositories = [];
        $this->output = new SymfonyStyle($input, $output);
        $config = new Config(\rtrim($input->getOption('path') ?? $this->rootPath, '\/'), $input->getOption('cache'), $input->getOption('clean'));
        $exclusive = $input->getOption('only');

        if (empty($jobWorkers = $config['workers'][$stage = $input->getArgument('job')] ?? [])) {
            $this->output->error(\sprintf('There are workflow workers registered. Be sure to add them to "%s"', $stage));

            return;
        }

        foreach ($jobWorkers as $offset => $worker) {
            $worker = $worker::configure($this);

            if ($worker instanceof PriorityInterface && $offset !== $worker->getPriority()) {
                $this->output->error(\sprintf(
                    'The "%s" worker must indexed "%s" and not "%s" in stage "%s"',
                    $worker::class,
                    $worker->getPriority(),
                    $offset,
                    $stage
                ));
                break;
            }

            $this->workers[] = $worker;
        }

        foreach ($config['repositories'] as $repoName => $data) {
            if ($exclusive && !\in_array($repoName, $exclusive, true)) {
                continue;
            }

            if (\in_array($stage, $data['workers'] ?? ['main'], true)) {
                $repositories[] = [$data['url'], $repoName, $data['path'], $data['merge'] ? 'true' : 'false'];
            }
        }

        $this->output->table(['Repo Url', 'Repo Name', 'Repo Path', 'Allow Merge'], $repositories);
        $this->monorepo = new Monorepo($config, $output->getVerbosity(), $repositories);
        $input->bind($this->getDefinition());
    }
}
