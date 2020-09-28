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

namespace Biurad\Http\Bridges\Nette;

use Biurad\Http\Sessions\HandlerFactory;
use Biurad\Http\Strategies\CookieJar;
use Nette;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;

class SessionExtension extends Nette\DI\CompilerExtension
{
    /** @var string */
    private $tempPath;

    public function __construct(string $tempPath)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Expect::structure([
            'driver'    => Expect::string()->before(function ($value) {
                return 'filesystem' === $value ? $this->tempPath : $value;
            }),
            'options'   => Nette\Schema\Expect::array(),
            'pools'     => Nette\Schema\Expect::structure([
                'name'          => Nette\Schema\Expect::string()->default('BF_SESSID'),
                'maxlifetime'   => Nette\Schema\Expect::int()->default(7200),
                'path'          => Nette\Schema\Expect::string()->default('/'),
                'domain'        => Nette\Schema\Expect::string()->nullable(),
                'secure'        => Nette\Schema\Expect::bool()->default(false),
                'httponly'      => Nette\Schema\Expect::bool()->default(false),
            ])->castTo('array'),
        ])->castTo('array');
    }

    /**
     * {@inheritdoc}
     */
    public function loadConfiguration(): void
    {
        $pools   = $this->config['pools'];
        $builder = $this->getContainerBuilder();

        $this->config['options']['name']            = (string) $pools['name'];
        $this->config['options']['cookie_lifetime'] = (string) $pools['maxlifetime'];
        $this->config['options']['cookie_domain']   = (string) $pools['domain'];
        $this->config['options']['cookie_path']     = (string) $pools['path'];
        $this->config['options']['cookie_httponly'] = (string) ($pools['httponly'] ?: 0);
        $this->config['options']['cookie_secure']   = (string) ($pools['secure'] ?: 0);

        $builder->addDefinition($this->prefix('handler'))
            ->setFactory(
                new Statement(
                    [new Statement(HandlerFactory::class, ['minutes' => $pools['maxlifetime']]), 'createHandler']
                )
            )
            ->setArguments([$this->config['driver']])
            ->addSetup('setOptions', [$this->config['options']]);

        $session = $builder->addDefinition('session')
            ->setFactory(\Biurad\Http\Session::class)
            ->addSetup('setHandler', [new Reference($this->prefix('handler'))])
        ;

        if ('cookie' === $this->config['driver'] && $builder->hasDefinition('request')) {
            $session->addSetup('setRequestOnHandler');
        }

        if (!$builder->hasDefinition('cookie') && \class_exists(CookieJar::class)) {
            $builder->addDefinition('cookie')
                ->setFactory(CookieJar::class)
                ->addSetup('setDefaultPathAndDomain', [
                    $pools['path'], $pools['domain'], $pools['secure'],
                ]);
        }
    }
}
