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
 * Git object for handling git objects.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class GitObject implements \Stringable
{
    protected ?string $hash;
    protected Repository $repository;

    public function __construct(Repository $repository, string $commitHash)
    {
        $this->repository = $repository;
        $this->hash = $commitHash;
    }

    /**
     * Returns the SHA1 hash of the commit.
     */
    public function __toString(): string
    {
        $hash = $this->hash;

        if (empty($hash) || !\preg_match('/^[0-9a-f]{40}$/', $hash)) {
            throw new \InvalidArgumentException(\sprintf('Invalid commit hash%s', empty($hash) ? '. Empty hash provided' : " \"$hash\""));
        }

        return $hash;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }
}
