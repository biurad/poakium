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

use Nette\DI\Definitions\Reference;
use Nette\DI\ContainerBuilder;
use BiuradPHP\Security\Firewalls\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;

/**
 * FormLoginFactory creates services for form login authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FormLoginFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('username_parameter', '_username');
        $this->addOption('password_parameter', '_password');
        $this->addOption('csrf_parameter', '_csrf_token');
        $this->addOption('csrf_token_id', 'authenticate');
        $this->addOption('csrf_status', true);
        $this->addOption('post_only', true);
    }

    public function getPosition()
    {
        return 'form';
    }

    public function getKey()
    {
        return 'form-login';
    }

    protected function getListener()
    {
        return [UsernamePasswordFormAuthenticationListener::class, 'security.authentication.listener.form'];
    }

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
                ->setArgument(4, $container->getParameter('security.authentication.hide_user_not_found'))
                ->setAutowired(false)
            ;
        }

        return $provider;
    }

    protected function createListener(ContainerBuilder $container, string $id, array $config, string $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        $container
            ->getDefinition($listenerId)
            ->setArgument(8, isset($config['csrf_token_generator']) ? new Reference($config['csrf_token_generator']) : null)
        ;;

        return $listenerId;
    }
}
