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
 * User Identifier.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Identity
{
    private string $name, $email;
    private ?\DateTimeInterface $date = null;

    public function __construct(string $name, string $email, \DateTimeInterface|string $date = null)
    {
        $this->name = $name;
        $this->email = $email;

        if (null !== $date) {
            $this->setDate($date);
        }
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setDate(\DateTimeInterface|string $date): self
    {
        if (\is_string($date)) {
            $date = new \DateTimeImmutable(\is_numeric($date) ? '@'.$date : $date);
        }
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
}
