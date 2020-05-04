<?php /** @noinspection PhpUnusedParameterInspection */

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

use BiuradPHP\Security\Firewalls\AbstractListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use Nette\DI\ContainerBuilder;

/**
 * AbstractFactory is the base class for all classes inheriting from
 * AbstractAuthenticationListener.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractFactory implements SecurityFactoryInterface
{
    protected $options = [
        'check_path' => '/login_check',
        'use_forward' => false,
        'require_previous_session' => false,
    ];

    protected $defaultSuccessHandlerOptions = [
        'always_use_default_target_path' => false,
        'default_target_path' => '/',
        'login_path' => '/login',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    ];

    protected $defaultFailureHandlerOptions = [
        'failure_path' => null,
        'failure_forward' => false,
        'login_path' => '/login',
        'failure_path_parameter' => '_failure_path',
    ];

    public function create(ContainerBuilder $container, string $id, array $config, string $userProviderId)
    {
        // authentication provider
        $authProviderId = $this->createAuthProvider($container, $id, $config, $userProviderId);

        // authentication listener
        $listenerId = $this->createListener($container, $id, $config, $userProviderId);

        // add remember-me aware tag if requested
        if ($this->isRememberMeAware($config)) {
            $container
                ->getDefinition($listenerId)
                ->addTag('security.remember_me_aware', ['id' => $id, 'provider' => $userProviderId])
            ;
        }

        return [$authProviderId, $listenerId];
    }

    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        $configuration = [];
        foreach (array_merge($this->options, $this->defaultSuccessHandlerOptions, $this->defaultFailureHandlerOptions) as $name => $default) {
            if (is_bool($default)) {
                $configuration[$name] = $node::bool()->default($default);
            } else {
                $configuration[$name] = $node::string()->default($default);
            }
        }

        return $node::structure(array_merge([
            'provider'      => $node::string(),
            'remember_me'   => $node::bool(true),
        ], $configuration))->castTo('array');
    }

    final public function addOption(string $name, $default = null)
    {
        $this->options[$name] = $default;
    }

    /**
     * Subclasses must return the id of a service which implements the
     * AuthenticationProviderInterface.
     *
     * @return string never null, the id of the authentication provider
     */
    abstract protected function createAuthProvider(ContainerBuilder $container, string $id, array $config, string $userProviderId);

    /**
     * Subclasses must return the id of the abstract listener template.
     *
     * Listener definitions should inherit from the AbstractAuthenticationListener
     * like this:
     *
     * In the above case, this method would return AbstractListener instance as string,
     * and a string of listener id as eg: "my.listener.id".
     *
     * @return array<AbstractListener, string>
     */
    abstract protected function getListener();

    /**
     * Subclasses may disable remember-me features for the listener, by
     * always returning false from this method.
     *
     * @param array $config
     * @return bool Whether a possibly configured RememberMeServices should be set for this listener
     */
    protected function isRememberMeAware(array $config)
    {
        return $config['remember_me'] ?? false;
    }

    protected function createListener(ContainerBuilder $container, string $id, array $config, string $userProvider)
    {
        [$listener, $listenerId] = $this->getListener();

        $listenerId .= '.'.$id;
        $container->addDefinition($listenerId)
            ->setFactory($listener)
            ->setArgument(3, $id)
            ->setArgument(5, array_intersect_key($config, $this->options));

        return $listenerId;
    }
}
