<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Cache\Tests\Fixtures;

use Doctrine\Common\Cache\Cache;

class NullAdapterTest implements Cache
{
    /** @var array[] each element being a tuple of [$data, $expiration], where the expiration is int|bool */
    private $data = [];

    /** @var int */
    private $hitsCount = 0;

    /** @var int */
    private $missesCount = 0;

    /** @var int */
    private $upTime;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->upTime = \time();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if (!$this->contains($id)) {
            $this->missesCount += 1;

            return false;
        }

        $this->hitsCount += 1;

        return $this->data[$id][0];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        if (!isset($this->data[$id])) {
            return false;
        }

        $expiration = $this->data[$id][1];

        if ($expiration && $expiration < \time()) {
            $this->delete($id);

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $this->data[$id] = [$data, $lifeTime ? \time() + $lifeTime : false];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        unset($this->data[$id]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $this->data = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return [
            Cache::STATS_HITS             => $this->hitsCount,
            Cache::STATS_MISSES           => $this->missesCount,
            Cache::STATS_UPTIME           => $this->upTime,
            Cache::STATS_MEMORY_USAGE     => null,
            Cache::STATS_MEMORY_AVAILABLE => null,
        ];
    }

    public function getNamespace()
    {
        return '';
    }
}
