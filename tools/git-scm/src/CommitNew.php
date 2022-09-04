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
 * Represents a new git commit.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CommitNew
{
    /** @var array<int,mixed> */
    private array $data = [];

    /**
     * @param array<int,string>          $paths
     * @param array<int,Commit\Identity> $coAuthors
     */
    public function __construct(
        Commit\Message $message,
        array $paths = [],
        Commit\Identity $author = null,
        Commit\Identity $committer = null,
        array $coAuthors = []
    ) {
        $this->data = \func_get_args();
    }

    /**
     * @return array<int,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
