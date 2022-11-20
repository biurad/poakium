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

/**
 * A class for fetching git commit logs.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Log implements \Countable, \IteratorAggregate
{
    private array $revisions = [], $paths = [], $commits = [];

    /**
     * @param Repository                    $repository the repository where log occurs
     * @param null|array<int,string>|string $revisions  a list of revisions or null if you want all history
     * @param null|array<int,string>|string $paths      paths to filter on
     * @param null|int                      $offset     start list from a given position
     * @param null|int                      $limit      limit number of fetched elements
     */
    public function __construct(
        private Repository $repository,
        array|string|null $revisions = null,
        array|string $paths = null,
        private ?int $offset = null,
        private ?int $limit = null
    ) {
        $this->paths = \is_string($paths) ? [$paths] : $paths ?? [];
        $this->revisions = \is_string($revisions) ? [$revisions] : $revisions ?? [];
    }

    /**
     * Position to start listing commits from.
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Limit number of fetched commits.
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getRevisions(): array
    {
        return $this->revisions;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return array<int,Commit>
     */
    public function getCommits(): array
    {
        $args = ['--encoding='.'utf8', '--pretty='.'format:%H'];

        if (null !== $this->offset) {
            $args[] = '--skip='.$this->offset;
        }

        if (null !== $this->limit) {
            $args[] = '-n';
            $args[] = $this->limit;
        }

        $args = \array_merge($args, \count($this->revisions) > 0 ? $this->revisions : ['--all', '--'], $this->paths);
        $o = $this->repository->run('log', $args);

        if (empty($o) || 0 !== $this->repository->getExitCode()) {
            return [];
        }

        if (!isset($this->commits[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $commit) {
                $this->commits[$i][] = $this->repository->getCommit($commit);
            }
        }

        return $this->commits[$i];
    }

    public function count(): int
    {
        if (\count($this->revisions) > 0) {
            $count = (int) $this->repository->run('rev-list', ['--count', ...$this->revisions, '--', ...$this->paths]);
        }

        return $count ?? (int) $this->repository->run('rev-list', ['--count', '--all', '--', ...$this->paths]);
    }

    /**
     * @return \Traversable<Commit>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getCommits());
    }
}
