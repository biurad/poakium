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
 * A git commit message.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Message implements \Stringable
{
    public function __construct(private string $subject, private ?string $body = null)
    {
    }

    public function __toString(): string
    {
        $full = $this->subject;

        if (!empty($this->body)) {
            $full .= "\n".$this->body;
        }

        return $full;
    }

    public static function fromString(string $message): self
    {
        $data = \explode("\n\n", $message, 2);

        return new self($data[0] ?? '', $data[2] ?? null);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }
}
