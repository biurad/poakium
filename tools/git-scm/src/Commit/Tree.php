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

use Biurad\Git\{GitObject, Repository};

/**
 * The Tree object represents a Git tree object.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Tree extends GitObject
{
    protected bool $initialized = false;
    protected array $entries = [];

    public function __construct(Repository $repository, string $hash = null, protected ?int $mode = null)
    {
        parent::__construct($repository, $hash);
    }

    public function getMode(): ?int
    {
        return $this->mode;
    }

    /**
     * @return array<string,Blob|CommitRef|Tree> An associative array name => $object
     */
    public function getEntries(): array
    {
        $this->doInitialize();

        return $this->entries;
    }

    public function has(string $entry): bool
    {
        $this->doInitialize();

        return isset($this->entries[$entry]);
    }

    public function get(string $entry): Blob|Tree|CommitRef
    {
        $this->doInitialize();

        return $this->entries[$entry] ?? throw new \InvalidArgumentException(\sprintf('Tree entry "%s" does not exist.', $entry));
    }

    /**
     * Get a sub-tree of a given path.
     */
    public function getSubTree(string $path): Blob|Tree|CommitRef
    {
        $tree = $this;

        foreach (\explode('/', \ltrim($path, '/')) as $segment) {
            if (!empty($segment)) {
                $tree = $tree->get($segment);
            }
        }

        return $tree;
    }

    protected function doInitialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $o = $this->repository->run('ls-tree', [$this->__toString(),'--format=%(objectmode) %(objecttype) %(objectname) %(path)']);

        if (empty($o) || 0 !== $this->repository->getExitCode()) {
            throw new \RuntimeException(\sprintf('Failed to get tree data for "%s"', $this->__toString()));
        }

        foreach (\explode("\n", $o) as $line) {
            if (empty($line)) {
                continue;
            }
            [$a, $b, $c, $d] = \explode(' ', $line, 4);
            $object = null;

            if ('tree' === $b) {
                $object = new self($this->repository, $c, (int) $a);
            } elseif ('blob' === $b) {
                $object = new Blob($this->repository, $c, (int) $a);
            }
            $this->entries[$d] = $object ?? new CommitRef($c, (int) $a);
        }

        $this->initialized = true;
    }
}
