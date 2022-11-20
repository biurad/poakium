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

namespace Biurad\Git\Commit;

/**
 * A git ref of a commit containing only the hash.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CommitRef implements \Stringable
{
    private string $hash;

    public function __construct(string $commitHash, private ?int $mode = null)
    {
        $this->hash = $commitHash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }

    public function getMode(): ?int
    {
        return $this->mode;
    }
}
