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

namespace Biurad\Http\Sessions\Storage;

use Biurad\Http\Sessions\MetadataBag;

/**
 * Allows session to be started by PHP and managed by Biurad adopting Symfony Style.
 *
 * @author Drak <drak@zikula.org>
 */
class PhpBridgeSessionStorage extends NativeSessionStorage
{
    /**
     * @param null|AbstractProxy|\SessionHandlerInterface $handler
     */
    public function __construct($handler = null, MetadataBag $metaBag = null)
    {
        if (!\extension_loaded('session')) {
            throw new \LogicException('PHP extension "session" is required.');
        }

        $this->setMetadataBag($metaBag);
        $this->setSaveHandler($handler);
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $this->loadSession();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        // clear out the bags and nothing else that may be set
        // since the purpose of this driver is to share a handler
        foreach ($this->bags as $bag) {
            $bag->clear();
        }

        // reconnect the bags to the session
        $this->loadSession();
    }
}
