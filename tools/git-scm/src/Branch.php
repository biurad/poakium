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
 * Represents a git branch object.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Branch extends Revision
{
    private bool $remote = false;

    public function isRemote(): bool
    {
        return $this->remote;
    }

    public function isLocal(): bool
    {
        return !$this->remote;
    }

    public function getRemoteName(): ?string
    {
        return $this->remote ? \explode('/', $this->name, 2)[1] : null;
    }

    /**
     * @param null|int $offset Start listing from a given position
     * @param null|int $limit  Limit the fetched number of commits
     *
     * @return array<int,Commit>
     */
    public function getCommits(int $offset = null, int $limit = null): array
    {
        return $this->repository->getLog($this->getName(), null, $offset, $limit)->getCommits();
    }

    protected function doName(): string
    {
        if (\str_starts_with($ref = $this->revision, 'refs/')) {
            $name = \substr($this->revision, 11);

            if (\str_starts_with($name, 's/')) {
                $this->remote = true;
                $name = \substr($name, 2);
            }

            return $name;
        }

        // Maybe we should throw an exception here? I'm not sure.
        $this->revision = 'refs/heads/'.$this->revision;

        return $ref;
    }
}
