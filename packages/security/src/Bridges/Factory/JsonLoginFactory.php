<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\Bridges\Factory;

use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use BiuradPHP\Security\Firewalls\UsernamePasswordJsonAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;

/**
 * JsonLoginFactory creates services for JSON login authentication.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLoginFactory extends AbstractFactory
{
    protected $defaultSuccessHandlerOptions = [
        'always_use_default_target_path' => false,
        'default_target_path' => './',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    ];

    protected $defaultFailureHandlerOptions = [
        'failure_path' => null,
        'failure_forward' => false,
        'failure_path_parameter' => '_failure_path',
    ];

    public function __construct()
    {
        $this->addOption('username_path', 'username');
        $this->addOption('password_path', 'password');

        foreach (array_merge($this->defaultFailureHandlerOptions, $this->defaultSuccessHandlerOptions) as $key => $value) {
            $this->addOption($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'json-login';
    }

    /**
     * {@inheritdoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, string $id, array $config, string $userProviderId)
    {
        $provider = 'security.authentication.provider.dao.'.$id;
        if (! $container->hasDefinition($provider)) {
            $container
                ->addDefinition($provider)
                ->setFactory(DaoAuthenticationProvider::class)
                ->setArgument(0, new Reference($userProviderId))
                ->setArgument(1, new Reference('security.user_checker.'.$id))
                ->setArgument(2, $id)
                ->setAutowired(false)
            ;
        }

        return $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener()
    {
        return [UsernamePasswordJsonAuthenticationListener::class, 'security.authentication.listener.json'];
    }

    /**
     * {@inheritdoc}
     */
    protected function isRememberMeAware($config)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createListener(ContainerBuilder $container, string $id, array $config, string $userProvider)
    {
        [$listener, $listenerId] = $this->getListener();
        $listenerId .= '.'.$id;

        $listener = $container->addDefinition($listenerId)->setFactory($listener);
        $listener->setArgument(2, $id);
        $listener->setArgument(3, array_intersect_key($config, $this->options));
        $listener->addSetup('setSessionAuthenticationStrategy', [new Reference('security.authentication.session_strategy.'.$id)]);

        return $listenerId;
    }
}
