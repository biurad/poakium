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
 * Represents a git commit.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Commit extends GitObject
{
    /** @var array<string,mixed> */
    private array $data = [];

    private ?string $content = null;

    public static function fromRaw(string $rawString, Repository $repo, string $hash): self
    {
        $commit = new self($repo, $hash);
        $commit->content = $rawString;

        return $commit;
    }

    /**
     * @param bool $soft If false, repository will be reset to this commit
     * @param bool $keep Preserve uncommitted local changes if true
     */
    public function reset(bool $soft = true, bool $keep = false): void
    {
        $this->data = [];
        $this->repository->reset($soft ? null : $this->__toString(), $keep);
    }

    /**
     * Returns the commit as string.
     */
    public function getRaw(): string
    {
        return $this->content ??= $this->getData('content');
    }

    /**
     * Returns the short hash of a commit.
     */
    public function getShortHash(): string
    {
        return $this->data['shortHash'] ??= \substr((string) $this, 0, 7);
    }

    /**
     * @return array<int,string> An array of parent SHA1 hashes
     */
    public function getParentHashes(): array
    {
        return $this->getData('parent') ?? [];
    }

    /**
     * @return array<int,self> An array of parent commits
     */
    public function getParents(): array
    {
        $commits = [];

        foreach ($this->getParentHashes() as $parentHash) {
            $commits[] = $this->repository->getCommit($parentHash);
        }

        return $commits;
    }

    /**
     * Returns the tree hash of the commit.
     */
    public function getTreeHash(): string
    {
        return $this->getData('treeHash');
    }

    /**
     * Returns the tree object of the commit.
     */
    public function getTree(): Commit\Tree
    {
        return $this->data['tree'] ??= new Commit\Tree($this->repository, $this->getTreeHash());
    }

    /**
     * Returns the author of the commit.
     */
    public function getAuthor(): Commit\Identity
    {
        return $this->getData('author');
    }

    /**
     * Returns the committer of the commit.
     */
    public function getCommitter(): Commit\Identity
    {
        return $this->getData('committer');
    }

    /**
     * Returns the commit message.
     */
    public function getMessage(): Commit\Message
    {
        if (!isset($this->data['msg-object'])) {
            $data = \explode("\n\n", $this->getData('message'), 2);
            $this->data['msg-object'] = new Commit\Message($data[0], $data[1] ?? null);
        }

        return $this->data['msg-object'];
    }

    /**
     * @return null|string get the verified signature of a commit
     */
    public function getSignature(): ?string
    {
        return $this->getData('gsgSign');
    }

    /**
     * @return array<int,Revision> An array of (Branch, Tag, Squash)
     */
    public function getReferences(): array
    {
        $o = $this->repository->run('for-each-ref', ['--contains', $this->__toString(), '--format=%(refname) %(objectname)']);

        if (empty($o) || 0 !== $this->repository->getExitCode()) {
            throw new \RuntimeException(\sprintf('Failed to get commit references for "%s"', $this->__toString()));
        }

        if (!isset($this->data[$i = \md5($o)])) {
            foreach (\explode("\n", $o) as $line) {
                if (empty($line)) {
                    continue;
                }
                [$ref, $hash] = \explode(' ', $line, 2);

                if (\str_starts_with($ref, 'refs/tags/')) {
                    $this->data[$i][] = new Tag($this->repository, $ref, $hash);
                    continue;
                }

                $this->data[$i][] = new Branch($this->repository, $ref, $hash);
            }
        }

        return $this->data[$i];
    }

    private function getData(string $name): mixed
    {
        if (!isset($this->data[$name])) {
            $o = $this->content ?? $this->repository->run('cat-file', ['commit', $this->__toString()]);

            if (empty($o) || 0 !== $this->repository->getExitCode()) {
                throw new \RuntimeException(\sprintf('Failed to get commit data for "%s"', $this->__toString()));
            }

            foreach (\explode("\n", $this->data['content'] = $o) as $line) {
                if (isset($this->data['gpgSign'])) {
                    if (\str_ends_with($line, '-----END PGP SIGNATURE-----')) {
                        $pos = \strpos($o, "-----\n \n\n");
                        $this->data['message'] = \substr($o, $pos ? $pos + 9 : \strpos($o, "-----\n\n") + 7);
                        break;
                    }

                    if (!empty($line)) {
                        $this->data['gpgSign'] .= $line."\n";
                    }
                    continue;
                } elseif (empty($line)) {
                    continue;
                }
                [$key, $value] = \explode(' ', $line, 2);

                if ('tree' === $key) {
                    $this->data['treeHash'] = $value;
                } elseif ('parent' === $key) {
                    $this->data['parent'][] = $value;
                } elseif (\in_array($key, ['author', 'committer'], true)) {
                    \preg_match('/(([^\n]*) <([^\n]*)> (\d+ [+-]\d{4}))/A', $value, $author);
                    $this->data[$key] = new Commit\Identity($author[2], $author[3], \DateTime::createFromFormat('U e O', $author[4].' UTC'));
                } elseif ('gpgsig' === $key) {
                    $this->data['gpgSign'] = '';
                } else {
                    $this->data['message'] = \substr($o, \strpos($o, "\n\n") + 2);
                    break;
                }
            }
        }

        return $this->data[$name] ?? null;
    }
}
