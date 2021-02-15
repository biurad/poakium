<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\UI;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Profile implements \IteratorAggregate
{
    public const TEMPLATE = 'template';

    /** @var string */
    private $template;

    /** @var string */
    private $name;

    /** @var array<string,int|float> */
    private $starts = [];

    /** @var array<string,int|float> */
    private $ends = [];

    /** @var Profile[] */
    private $profiles = [];

    public function __construct(string $template = 'main', string $name = 'main')
    {
        $this->template = $template;
        $this->name     = $name;
        $this->enter();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isTemplate(): bool
    {
        return self::TEMPLATE === $this->template;
    }

    /**
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * @param Profile $profile
     */
    public function addProfile(self $profile): void
    {
        $this->profiles[] = $profile;
    }

    /**
     * Returns the duration in microseconds.
     *
     * @return float
     */
    public function getDuration(): float
    {
        if ($this->profiles) {
            // for the root node with children, duration is the sum of all child durations
            $duration = 0;

            foreach ($this->profiles as $profile) {
                $duration += $profile->getDuration();
            }

            return $duration;
        }

        return isset($this->ends['wt']) && isset($this->starts['wt']) ? $this->ends['wt'] - $this->starts['wt'] : 0;
    }

    /**
     * Returns the memory usage in bytes.
     *
     * @return int
     */
    public function getMemoryUsage(): int
    {
        return isset($this->ends['mu']) && isset($this->starts['mu']) ? $this->ends['mu'] - $this->starts['mu'] : 0;
    }

    /**
     * Returns the peak memory usage in bytes.
     *
     * @return int
     */
    public function getPeakMemoryUsage(): int
    {
        return isset($this->ends['pmu']) && isset($this->starts['pmu']) ? $this->ends['pmu'] - $this->starts['pmu'] : 0;
    }

    /**
     * Starts the profiling.
     */
    public function enter(): void
    {
        $this->starts = [
            'wt'  => \microtime(true),
            'mu'  => \memory_get_usage(),
            'pmu' => \memory_get_peak_usage(),
        ];
    }

    /**
     * Stops the profiling.
     *
     * @return static
     */
    public function leave(): self
    {
        $this->ends = [
            'wt'  => \microtime(true),
            'mu'  => \memory_get_usage(),
            'pmu' => \memory_get_peak_usage(),
        ];

        return $this;
    }

    public function reset(): void
    {
        $this->starts = $this->ends = $this->profiles = [];
        $this->enter();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->profiles);
    }
}
