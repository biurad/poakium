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
 * An object representing a git refname.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Revision extends GitObject
{
    protected string $name, $revision;

    /**
     * @param string $revision The revision name eg. (refs/heads/master)
     */
    public function __construct(Repository $repository, string $revision, string $commitHash = null)
    {
        parent::__construct($repository, $commitHash);
        $this->revision = $revision;
        $this->name = $this->doName();
    }

    public function __toString(): string
    {
        if (null === $this->hash) {
            try {
                $this->hash = $this->repository->run('rev-parse', ['--verify', $this->revision]);
            } catch (ExceptionInterface) {
            }
        }

        return parent::__toString();
    }

    /**
     * The name of the revision (e.g. "refs/heads/master").
     */
    public function getRevision(): string
    {
        return $this->revision;
    }

    /**
     * The filtered name of the revision (e.g. "master").
     */
    public function getName(): string
    {
        return $this->name ?: throw new \RuntimeException('Not implemented yet.');
    }

    public function getCommit(): ?Commit
    {
        return $this->repository->getCommit($this->__toString());
    }

    protected function doName(): string
    {
        return ''; // Should be extendedly implemented
    }
}
