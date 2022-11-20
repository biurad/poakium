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

use Biurad\Git\Repository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Monorepo Manager Class.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Monorepo
{
    private Repository $repository;
    private ?LoggerInterface $logger = null;
    private bool $debug;

    /**
     * @param array<int,array<int,string>> $repositories
     */
    public function __construct(public Config $config, int $verbosity, private array $repositories)
    {
        $verMap = [
            'error' => ConsoleOutput::VERBOSITY_VERY_VERBOSE,
            'debug' => ConsoleOutput::VERBOSITY_VERBOSE,
        ];
        $this->repository = new Repository(
            $this->config['path'],
            [],
            $this->debug = false !== \getenv('APP_DEBUG'),
            $this->logger = \interface_exists(LoggerInterface::class) ? new ConsoleLogger(new ConsoleOutput($verbosity), $verMap) : null
        );

        if (0 !== $this->repository->getConfig('gc.auto')) {
            $this->repository->run('config', ['gc.auto', 0]);
        }
    }

    public function resolveRepository(OutputInterface $output, callable $resolver, callable $checker = null): int
    {
        $result = 0;

        foreach ($this->repositories as [$url, $remote, $path, $merge]) {
            if (null !== $checker && $checker([$url, $remote, $path, $merge])) {
                continue;
            }

            if (\file_exists($clonePath = \rtrim($this->config['cache'], '\/').'/'.$remote)) {
                $output->writeln(\sprintf('Deleting previous clone of <info>%s</info>', $remote));
                Process::fromShellCommandline(('\\' === \DIRECTORY_SEPARATOR ? 'rd /s /q "' : 'rm -rf "').$clonePath.'"')->run();
            }

            $output->writeln(\sprintf('<info>Cloning %s into %s</info>', $url, $clonePath));
            \mkdir($clonePath, recursive: true);
            $this->repository->run('clone', ['-q', '--bare', $url, $clonePath]);

            if (0 !== $this->repository->getExitCode()) {
                $output->writeln(\sprintf('<error>Failed to clone %s</error>', $url));

                return WorkflowCommand::FAILURE;
            }

            if (null === $this->repository->getConfig('remote.'.$remote.'.url')) {
                $output->writeln(\sprintf('<info>Adding remote %s</info>', $remote));
                $this->repository->run('remote', ['add', $remote, $clonePath]);
            } else {
                $output->writeln(\sprintf('<info>Updating remote %s</info>', $remote));
                $this->repository->run('remote', ['set-url', $remote, $clonePath]);
            }

            $result = $resolver([$url, $remote, $path, $clonePath, $merge]);
            $this->repository->run('remote', ['remove', $remote]);
            $output->writeln('');

            if (null === $result) {
                continue;
            }

            if ($result > 0) {
                return $result;
            }
        }

        return $result;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * @return array<int,array<int,string>>
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }
}
