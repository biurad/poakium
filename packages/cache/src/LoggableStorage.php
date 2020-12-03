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

namespace Biurad\Cache;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FlushableCache;

final class LoggableStorage extends CacheProvider
{
    public const KEY_METHOD = 'method';

    public const KEY_KEY = 'key';

    public const KEY_DATA = 'data';

    public const KEY_OPTIONS = 'options';

    /** @var DoctrineCache */
    private $storage;

    /** @var mixed[] */
    private $options = [
        'maxCalls' => 100,
    ];

    /** @var mixed[] */
    private $calls = [];

    public function __construct(DoctrineCache $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = \array_merge($this->options, $options);
    }

    /**
     * @return mixed[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $data = $this->storage->fetch($id);

        $this->addLog(__FUNCTION__, $id, $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        return $this->storage->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $this->addLog(__FUNCTION__, $id, $data, ['lifetime' => $lifeTime]);

        return $this->storage->save($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $this->addLog(__FUNCTION__, $id);

        return $this->storage->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        if (!$this->storage instanceof FlushableCache) {
            return false;
        }

        return $this->storage->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return $this->storage->getStats();
    }

    /**
     * @param string  $method
     * @param mixed   $key
     * @param mixed   $data
     * @param mixed[] $options
     */
    private function addLog(string $method, $key, $data = null, array $options = []): void
    {
        $this->calls[] = [
            self::KEY_METHOD  => $method,
            self::KEY_KEY     => (string) $key,
            self::KEY_DATA    => $data,
            self::KEY_OPTIONS => $options,
        ];

        if (\count($this->calls) > $this->options['maxCalls']) {
            \array_shift($this->calls);
        }
    }
}
