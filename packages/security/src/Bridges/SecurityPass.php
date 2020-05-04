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

namespace BiuradPHP\Security\Bridges;

use LogicException;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use BiuradPHP\Security\AccessMap;
use BiuradPHP\Security\Config\FirewallConfig;
use BiuradPHP\Security\FirewallContext;
use BiuradPHP\Security\FirewallMap;
use BiuradPHP\Security\Firewalls\AccessListener;
use BiuradPHP\Security\Firewalls\ChannelListener;
use BiuradPHP\Security\Firewalls\ContextListener;
use BiuradPHP\Security\Firewalls\ExceptionListener;
use BiuradPHP\Security\Concerns\FakeEventDispatcher;
use BiuradPHP\Security\Firewalls\LogoutListener;
use BiuradPHP\Security\Firewalls\SwitchUserListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use BiuradPHP\Security\Exceptions\InvalidConfigurationException;
use BiuradPHP\Security\Interfaces\UserProviderFactoryInterface;
use BiuradPHP\Security\LazyFirewallContext;
use BiuradPHP\Security\Logout\CookieClearingLogoutHandler;
use BiuradPHP\Security\Logout\CsrfTokenClearingLogoutHandler;
use BiuradPHP\Security\Logout\LogoutUrlGenerator;
use BiuradPHP\Security\Logout\SessionLogoutHandler;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\MissingUserProvider;

class SecurityPass
{
    private $extension;

    private $requestMatchers = [];
    private $contextListeners = [];
    private $listenerPositions = ['pre_auth', 'form', 'http', 'remember_me', 'anonymous'];
    private $factories = [];
    private $userProviderFactories = [];
    private $statelessFirewallKeys = [];

    private function __construct(CompilerExtension $extension)
    {
        $this->extension = $extension;

        foreach ($this->listenerPositions as $position) {
            $this->factories[$position] = [];
        }
    }

    public static function of(CompilerExtension $extension): self
    {
        return new self($extension);
    }

    public function addSecurityListenerFactory(SecurityFactoryInterface $factory)
    {
        $this->factories[$factory->getPosition()][] = $factory;
    }

    public function addUserProviderFactory(UserProviderFactoryInterface $factory)
    {
        $this->userProviderFactories[] = $factory;
    }

    public function createParameters(array $config, ContainerBuilder $container)
    {
        // set some global scalars
        $container->parameters[$this->extension->getName()]['access']['denied_url'] = $config['access_denied_url'];
        $container->parameters[$this->extension->getName()]['authentication']['manager']['erase_credentials'] = $config['erase_credentials'];
        $container->parameters[$this->extension->getName()]['authentication']['session_strategy']['strategy'] = $config['session_fixation_strategy'];
        $container->parameters[$this->extension->getName()]['access']['always_authenticate_before_granting'] = $config['always_authenticate_before_granting'];
        $container->parameters[$this->extension->getName()]['authentication']['hide_user_not_found'] = $config['hide_user_not_found'];
    }

    public function createEncoders(array $config, ContainerBuilder $container)
    {
        $encoderMap = [];
        $encoders = $config['encoders'];

        foreach ($encoders as $class => $encoder) {
            $encoderMap[$class] = Compilers\UserEncoderPass::createEncoder($encoder);
        }
        $container->addDefinition($this->extension->prefix('user_password_encoder.generic'))->setFactory(UserPasswordEncoder::class);

        return $container
            ->addDefinition($this->extension->prefix('encoder_factory.generic'))
            ->setFactory(EncoderFactory::class)
            ->setArguments([$encoderMap]);
    }

    public function createRoleHierarchy(array $config, ContainerBuilder $container)
    {
        $container->parameters[$this->extension->getName()]['role_hierarchy']['roles'] = $config['role_hierarchy'];
        $roleHierarchys = [new Statement(RoleVoter::class), new Statement(RoleHierarchyVoter::class), new Statement(AuthenticatedVoter::class)];

       $container->addDefinition($this->extension->prefix('role_hierarchy'))
            ->setFactory(RoleHierarchy::class)
            ->setArguments([$container->parameters[$this->extension->getName()]['role_hierarchy']['roles']]);

        if (!isset($config['role_hierarchy']) || 0 === count($config['role_hierarchy'])) {
            unset($roleHierarchys[1]);
        }
        unset($roleHierarchys[0]);

        return $roleHierarchys;
    }

    public function createAuthorization(array $config, ContainerBuilder $container)
    {
        $accessMap = $container->addDefinition($this->extension->prefix('access_map'))->setFactory(AccessMap::class);

        foreach ($config['access_control'] as $access) {
            $matcher = $this->createRequestMatcher(
                $access['path'] ?? null,
                $access['host'] ?? null,
                $access['port'] ?? null,
                $access['methods'] ?? [],
                $access['ips'] ?? null
            );

            $attributes = $access['roles'];
            $accessMap
                ->addSetup('add', [$matcher, is_string($attributes) ? [$attributes] : $attributes, $access['requires_channel'] ?? null]);
        }
    }

    public function createFirewalls(array $config, ContainerBuilder $container)
    {
        if (!isset($config['firewalls'])) {
            return;
        }

        $firewalls = $config['firewalls'];
        $providerIds = $this->createUserProviders($config, $container);

        if (count($providerIds) > 1) {
            foreach ($providerIds as $provider) {
                $container->getDefinition($provider)->setAutowired(false);
            }

            $container->addDefinition($this->extension->prefix('user.provider.chain'))
                ->setFactory(ChainUserProvider::class)
                ->setArguments([array_map(function ($provider) { return new Reference($provider); }, array_values($providerIds))]);
        }

        $customUserChecker = false;

        // load firewall map
        $mapDef = $container->addDefinition($this->extension->prefix('firewall.map'))->setFactory(FirewallMap::class);
        $map = $authenticationProviders = $contextRefs = [];
        foreach ($firewalls as $name => $firewall) {
            if (isset($firewall['user_checker']) && $this->extension->prefix('user_checker') !== $firewall['user_checker']) {
                $customUserChecker = true;
            }

            $configId = $this->extension->prefix('firewall.map.config.'.$name);
            [$matcher, $listeners, $exceptionListener, $logoutListener] = $this->createFirewall($container, $name, $firewall, $authenticationProviders, $providerIds, $configId);

            $contextId = $this->extension->prefix('firewall.map.context.'.$name);
            $context = new Statement($firewall['stateless'] || empty($firewall['anonymous']['lazy']) ? FirewallContext::class : LazyFirewallContext::class);
            $context = $container->addDefinition($contextId)->setFactory($context);
            $context
                ->setArgument(0, $listeners)
                ->setArgument(1, $exceptionListener)
                ->setArgument(2, $logoutListener)
                ->setArgument(3, new Reference($configId))
            ;

            $contextRefs[$contextId] = new Reference($contextId);
            $map[$contextId] = $matcher;
        }
        $mapDef->setArgument(1, $map);

        // add authentication providers to authentication manager
        $authenticationProviders = array_map(function ($id) {
            return new Reference($id);
        }, array_values(array_unique($authenticationProviders)));

        $container
            ->addDefinition($this->extension->prefix('authentication.manager'))
            ->setFactory(AuthenticationProviderManager::class)
            ->setArgument(0, $authenticationProviders)
            ->setArgument(1, $container->parameters[$this->extension->getName()]['authentication']['manager']['erase_credentials'])
            ->addSetup('setEventDispatcher', [new Statement(FakeEventDispatcher::class)])
        ;
    }

    private function createFirewall(ContainerBuilder $container, string $id, array $firewall, array &$authenticationProviders, array $providerIds, string $configId)
    {
        $config = $container->addDefinition($configId)->setFactory(new Statement(FirewallConfig::class));
        $config->setArgument(0, $id);
        $config->setArgument(1, $firewall['user_checker']);

        // Matcher
        $matcher = null;
        if (isset($firewall['request_matcher'])) {
            $matcher = new Reference($firewall['request_matcher']);
        } elseif (isset($firewall['pattern']) || isset($firewall['host'])) {
            $pattern = isset($firewall['pattern']) ? $firewall['pattern'] : null;
            $host = isset($firewall['host']) ? $firewall['host'] : null;
            $methods = isset($firewall['methods']) ? $firewall['methods'] : [];
            $matcher = $this->createRequestMatcher($pattern, $host, null, $methods);
        }

        $config->setArgument(2, $matcher ? 'BiuradPHP\Http\Matcher' : null);
        $config->setArgument(3, $firewall['security']);

        // Security disabled?
        if (false === $firewall['security']) {
            return [$matcher, [], null, null];
        }

        $config->setArgument(4, $firewall['stateless']);

        // Provider id (must be configured explicitly per firewall/authenticator if more than one provider is set)
        $defaultProvider = null;
        if (isset($firewall['provider'])) {
            if (!isset($providerIds[$normalizedName = str_replace('-', '_', $firewall['provider'])])) {
                throw new InvalidConfigurationException(sprintf('Invalid firewall "%s": user provider "%s" not found.', $id, $firewall['provider']));
            }
            $defaultProvider = $providerIds[$normalizedName];
            $container->removeDefinition($this->extension->prefix('user.provider.chain'));
        } elseif (1 === count($providerIds)) {
            $defaultProvider = reset($providerIds);
            $container->removeDefinition($this->extension->prefix('user.provider.chain'));
        } elseif (count($providerIds) > 1) {
            $defaultProvider = $this->extension->prefix(sprintf('user.provider.chain.%s', $id));
            $container->addAlias($defaultProvider, str_replace(".{$id}", '', $defaultProvider));
        }

        $config->setArgument(5, $defaultProvider);

        // Register listeners
        $listeners = [];
        $listenerKeys = [];

        // Channel listener
        $listeners[] = new Statement(ChannelListener::class);

        $contextKey = null;
        $contextListenerId = null;
        // Context serializer listener
        if (false === $firewall['stateless']) {
            $contextKey = $firewall['context'] ?? $id;
            $listeners[] = new Reference($contextListenerId = $this->createContextListener($container, $providerIds, $contextKey));
            $sessionStrategyId = $this->extension->prefix('authentication.session_strategy');
        } else {
            $this->statelessFirewallKeys[] = $id;
            $sessionStrategyId = $this->extension->prefix('authentication.session_strategy_noop');
        }
        $container->addAlias($this->extension->prefix('authentication.session_strategy.'.$id), $sessionStrategyId);

        $config->setArgument(6, $contextKey);

        // Logout listener
        $logoutListenerId = null;
        if (isset($firewall['logout'])) {
            $logoutListenerId = $this->extension->prefix('logout_listener.'.$id);
            $logoutListener = $container->addDefinition($logoutListenerId)->setFactory(new Statement(LogoutListener::class));
            $logoutListener->setArgument(1, [
                'csrf_parameter' => $firewall['logout']['csrf_parameter'],
                'csrf_token_id' => $firewall['logout']['csrf_token_id'],
                'csrf_status' => $firewall['logout']['csrf_status'],
                'logout_path' => $firewall['logout']['path'],
            ]);

            // add logout success handler target path
            $logoutListener->setArgument(2, $firewall['logout']['target']);

            // add CSRF provider
            if (isset($firewall['logout']['csrf_token_generator'])) {
                $logoutListener->setArguments([new Reference($firewall['logout']['csrf_token_generator'])]);
            }

            // add session logout handler
            if (true === $firewall['logout']['invalidate_session'] && false === $firewall['stateless']) {
                $logoutListener->addSetup('addHandler', [new Statement(SessionLogoutHandler::class)]);
            }

            // add csrf token handler
            if (true === $firewall['logout']['csrf_status'] && false === $firewall['stateless']) {
                $logoutListener->addSetup('addHandler', [new Statement(CsrfTokenClearingLogoutHandler::class)]);
            }

            // add cookie logout handler
            if (count($firewall['logout']['delete_cookies']) > 0) {
                $logoutListener->addSetup('addHandler', [new Statement(CookieClearingLogoutHandler::class, [$firewall['logout']['delete_cookies']])]);
            }

            // add custom handlers
            foreach ($firewall['logout']['handlers'] as $handlerId) {
                $handlerId = class_exists($handlerId) ? new Statement($handlerId) : new Reference($handlerId);
                $logoutListener->addSetup('addHandler', [$handlerId]);
            }

            // register with LogoutUrlGenerator
            $container
                ->addDefinition($this->extension->prefix('logout_url_generator'.$id))
                ->setFactory(LogoutUrlGenerator::class)
                ->addSetup('registerListener', [
                    $id,
                    $firewall['logout']['path'],
                    $firewall['logout']['csrf_token_id'],
                    $firewall['logout']['csrf_parameter'],
                    isset($firewall['logout']['csrf_token_generator']) ? new Reference($firewall['logout']['csrf_token_generator']): null,
                    false === $firewall['stateless'] && isset($firewall['context']) ? $firewall['context'] : null,
                ])
            ;
        }

        // Authentication listeners
        $authListeners = $this->createAuthenticationListeners($container, $id, $firewall, $authenticationProviders, $defaultProvider, $providerIds, $contextListenerId);

        $listeners = array_merge($listeners, $authListeners);

        // Switch user listener
        if (isset($firewall['switch_user'])) {
            $listenerKeys[] = 'switch_user';
            $listeners[] = new Reference($this->createSwitchUserListener($container, $id, $firewall['switch_user'], $defaultProvider, $firewall['stateless']));
        }

        // Access listener
        $listeners[] = new Statement(AccessListener::class);

        // Exception listener
        $exceptionListener = new Reference($this->createExceptionListener($container, $firewall, $id, $firewall['stateless']));

        $config->setArgument(8, isset($firewall['access_denied_handler']) ? $firewall['access_denied_handler'] : null);
        $config->setArgument(9, isset($firewall['access_denied_url']) ? $firewall['access_denied_url'] : null);

        $container->addAlias($this->extension->prefix('user_checker.'.$id), $firewall['user_checker']);

        foreach ($this->factories as $position) {
            foreach ($position as $factory) {
                $key = str_replace('-', '_', $factory->getKey());
                if (array_key_exists($key, $firewall)) {
                    $listenerKeys[] = $key;
                }
            }
        }

        $config->setArgument(10, $listenerKeys);
        $config->setArgument(11, isset($firewall['switch_user']) ? $firewall['switch_user'] : null);

        return [$matcher, $listeners, $exceptionListener, null !== $logoutListenerId ? new Reference($logoutListenerId) : null];
    }

    private function createContextListener(ContainerBuilder $container, array $providerIds, string $contextKey)
    {
        if (isset($this->contextListeners[$contextKey])) {
            return $this->contextListeners[$contextKey];
        }

        // make the ContextListener aware of the configured user providers
        $userProviders = [];

        foreach ($providerIds as $userProviderId) {
            $userProviders[] = new Reference($userProviderId);
        }

        $listenerId = $this->extension->prefix('context_listener.' . count($this->contextListeners));
        $listener = $container->addDefinition($listenerId)->setFactory(new Statement(ContextListener::class));
        $listener->setArgument(1, $userProviders);
        $listener->setArgument(2, $contextKey);
        $listener->setArgument(6, [new Reference($this->extension->prefix('token_storage')), 'enableUsageTracking']);

        return $this->contextListeners[$contextKey] = $listenerId;
    }

    private function createAuthenticationListeners(ContainerBuilder $container, string $id, array $firewall, array &$authenticationProviders, ?string $defaultProvider, array $providerIds, string $contextListenerId = null)
    {
        $listeners = [];
        $hasListeners = false;

        foreach ($this->listenerPositions as $position) {
            foreach ($this->factories[$position] as $factory) {
                $key = str_replace('-', '_', $factory->getKey());

                if (isset($firewall[$key])) {
                    if (isset($firewall[$key]['provider'])) {
                        if (!isset($providerIds[$normalizedName = str_replace('-', '_', $firewall[$key]['provider'])])) {
                            throw new InvalidConfigurationException(sprintf('Invalid firewall "%s": user provider "%s" not found.', $id, $firewall[$key]['provider']));
                        }
                        $userProvider = $providerIds[$normalizedName];
                    } elseif ('remember_me' === $key || 'anonymous' === $key) {
                        // RememberMeFactory will use the firewall secret when created, AnonymousAuthenticationListener does not load users.
                        $userProvider = null;

                        if ('remember_me' === $key && $contextListenerId) {
                            $container->getDefinition($contextListenerId)->addTag($this->extension->prefix('remember_me_aware'), ['id' => $id, 'provider' => 'none']);
                        }
                    } elseif ($defaultProvider) {
                        $userProvider = $defaultProvider;
                    } elseif (empty($providerIds)) {
                        $userProvider = $this->extension->prefix(sprintf('user.provider.missing.%s', $key));
                        if (! $container->hasDefinition($userProvider)) {
                            $container->addDefinition($userProvider)->setFactory(MissingUserProvider::class)->setArgument(0, $id);
                        }
                    } else {
                        throw new InvalidConfigurationException(sprintf('Not configuring explicitly the provider for the "%s" listener on "%s" firewall is ambiguous as there is more than one addDefinitioned provider.', $key, $id));
                    }

                    [$provider, $listenerId] = $factory->create($container, $id, $firewall[$key], $userProvider);

                    $listeners[] = new Reference($listenerId);
                    $authenticationProviders[] = $provider;
                    $hasListeners = true;
                }
            }
        }

        if (false === $hasListeners) {
            throw new InvalidConfigurationException(sprintf('No authentication listener addDefinitioned for firewall "%s".', $id));
        }

        return $listeners;
    }

    // Parses user providers and returns an array of their ids
    private function createUserProviders(array $config, ContainerBuilder $container): array
    {
        $providerIds = [];
        foreach ($config['providers'] as $name => $provider) {
            $id = $this->createUserDaoProvider($name, $provider, $container);
            $providerIds[str_replace('-', '_', $name)] = $id;
        }

        return $providerIds;
    }

    // Parses a <provider> tag and returns the id for the related user provider service
    private function createUserDaoProvider(string $name, array $provider, ContainerBuilder $container): string
    {
        $name = $this->getUserProviderId($name);

        // ORM Entity and In-memory DAO provider are managed by factories
        foreach ($this->userProviderFactories as $factory) {
            $key = str_replace('-', '_', $factory->getKey());

            if (!empty($provider[$key])) {
                $factory->create($container, $name, $provider[$key]);

                return $name;
            }
        }

        // Existing DAO service provider
        if (isset($provider['id'])) {
            if ($container->hasDefinition($provider['id'])) {
                $container->addAlias($name, $provider['id']);

                return $provider['id'];
            }
        }

        throw new InvalidConfigurationException(sprintf('Unable to create definition for "%s" user provider', $name));
    }

    private function getUserProviderId(string $name): string
    {
        return $this->extension->prefix('user.provider.concrete.'.strtolower($name));
    }

    private function createExceptionListener(ContainerBuilder $container, array $config, string $id, bool $stateless): string
    {
        $exceptionListenerId = $this->extension->prefix('exception_listener.'.$id);
        $listener = $container->addDefinition($exceptionListenerId)->setFactory(new Statement(ExceptionListener::class));
        $listener->setArgument(3, $id);
        $listener->setArgument(6, $stateless);

        // access denied handler setup
        if (isset($config['access_denied_handler'])) {
            $listener->setArgument(2, new Reference($config['access_denied_handler']));
        } elseif (isset($config['access_denied_url'])) {
            $listener->setArgument(5, $config['access_denied_url']);
        }

        return $exceptionListenerId;
    }

    private function createSwitchUserListener(ContainerBuilder $container, string $id, array $config, ?string $defaultProvider, bool $stateless): string
    {
        $userProvider = isset($config['provider']) ? $this->getUserProviderId($config['provider']) : $defaultProvider;

        if (!$userProvider) {
            throw new InvalidConfigurationException(sprintf('Not configuring explicitly the provider for the "switch_user" listener on "%s" firewall is ambiguous as there is more than one addDefinitioned provider.', $id));
        }

        $switchUserListenerId = $this->extension->prefix('authentication.switchuser_listener.'.$id);
        $listener = $container->addDefinition($switchUserListenerId)->setFactory(SwitchUserListener::class);
        $listener->setArgument(1, new Reference($userProvider));
        $listener->setArgument(2, new Reference($this->extension->prefix('user_checker.'.$id)));
        $listener->setArgument(3, $id);
        $listener->setArgument(6, $config['parameter']);
        $listener->setArgument(7, $config['role']);
        $listener->setArgument(9, $stateless);

        return $switchUserListenerId;
    }

    private function createRequestMatcher(string $path = null, string $host = null, int $port = null, array $methods = [], array $ips = null, array $attributes = []): Statement
    {
        if ($methods) {
            $methods = array_map('strtoupper', (array) $methods);
        }

        if (null !== $ips) {
            foreach ($ips as $ip) {
                if (!$this->isValidIp($ip)) {
                    throw new LogicException(sprintf('The given value "%s" in the "security.access_control" config option is not a valid IP address.', $ip));
                }
            }
        }

        $id = $this->extension->prefix('request_matcher.'.md5(serialize([$path, $host, $port, $methods, $ips, $attributes])));

        if (isset($this->requestMatchers[$id])) {
            return $this->requestMatchers[$id];
        }

        // only add arguments that are necessary
        $arguments = [$path, $host, $methods, $ips, $attributes, null, $port];
        while (count($arguments) > 0 && !end($arguments)) {
            array_pop($arguments);
        }

        return $this->requestMatchers[$id] = new Statement('BiuradPHP\Http\Matcher', $arguments);
    }

    private function isValidIp(string $cidr): bool
    {
        $cidrParts = explode('/', $cidr);

        if (1 === count($cidrParts)) {
            return false !== filter_var($cidrParts[0], FILTER_VALIDATE_IP);
        }

        $ip = $cidrParts[0];
        $netmask = $cidrParts[1];

        if (!ctype_digit($netmask)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $netmask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $netmask <= 128;
        }

        return false;
    }
}

