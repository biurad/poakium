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

use Symfony\Component\Process\Exception\ExceptionInterface;

/**
 * Represents a git tag object.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Tag extends Revision
{
    public function isAnnotated(): bool
    {
        try {
            $tag = $this->repository->run('cat-file', ['tag', $this->revision]);
        } catch (ExceptionInterface) {
        }

        return null !== ($tag ?? null);
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
        if (\str_starts_with($ref = $this->revision, 'refs/tags/')) {
            return \substr($this->revision, 10);
        }

        // Maybe we should throw an exception here? I'm not sure.
        $this->revision = 'refs/tags/'.$this->revision;

        return $ref;
    }
}
