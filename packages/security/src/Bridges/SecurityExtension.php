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

use BiuradPHP\MVC\Bridges\FrameworkExtension;
use BiuradPHP\Security\Commands\UserPasswordEncoderCommand;
use BiuradPHP\Security\Commands\UserStatusCommand;
use BiuradPHP\Security\Encrypter;
use BiuradPHP\Security\Password\PasswordValidator;
use BiuradPHP\Security\Session\CsrfTokenStorage;
use BiuradPHP\Security\Session\SessionAuthenticationStrategy;
use BiuradPHP\Security\User\Providers\EntityUserProvider;
use BiuradPHP\Security\User\UserFirewall;
use Nette, BiuradPHP;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;

class SecurityExtension extends Nette\DI\CompilerExtension
{
    private $factories = [];
    private $userProviderFactories = [];

    public function __construct()
    {
        $this->factories = [
            new Factory\FormLoginFactory(),
            new Factory\JsonLoginFactory(),
            new Factory\HttpBasicFactory(),
            new Factory\RememberMeFactory(),
            new Factory\X509Factory(),
            new Factory\RemoteUserFactory(),
            new Factory\AnonymousFactory(),
        ];

        $this->userProviderFactories = [
            new Providers\InMemoryFactory(),
            new Providers\EntityFactory('entity', EntityUserProvider::class),
        ];
    }

    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        $configSchema = new SecurityConfiguration($this->factories, $this->userProviderFactories);

        return $configSchema->getConfigTree();
    }

    /**
     * @internal
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        // Default Services...
        $builder->addDefinition($this->prefix('csrf.token_manager'))
            ->setFactory(new Statement(
                CsrfTokenManager::class, [new Statement(UriSafeTokenGenerator::class), new Statement(CsrfTokenStorage::class)]
            ))->setType(CsrfTokenManagerInterface::class);

        $builder->addDefinition($this->prefix('password.validator'))
            ->setFactory(PasswordValidator::class)
            ->setArguments([$this->config['passwords']]);

        $builder->addDefinition($this->prefix('encrypter'))
            ->setFactory(Encrypter::class)
            ->setArguments([
                $builder->parameters['env']['SECRET'] ?? $this->config['encryption']['secret'],
                $builder->parameters['env']['CIPHER'] ?? $this->config['encryption']['cipher'],
            ]);

        $builder->addDefinition($this->prefix('user_checker'))->setFactory(UserChecker::class);


        if (false !== $framework = current($this->compiler->getExtensions(FrameworkExtension::class))) {
            if (true !== $framework->getConfig()['security']) {
                return;
            }
        }

        $security = SecurityPass::of($this);
        $security->createParameters($this->config, $builder);

        // Register Listeners and other needed services first
        $builder->addDefinition($this->prefix('authentication.trust_resolver'))
            ->setFactory(AuthenticationTrustResolver::class);

        $builder->addDefinition($this->prefix('authentication.session_strategy'))
            ->setFactory(SessionAuthenticationStrategy::class)
            ->setArguments([$builder->parameters[$this->name]['authentication']['session_strategy']['strategy']]);

        $builder->addDefinition($this->prefix('authentication.session_strategy_noop'))
            ->setFactory(SessionAuthenticationStrategy::class)
            ->setArguments(['none'])->setAutowired(false);

        foreach ($this->factories as $factory) {
            $security->addSecurityListenerFactory($factory);
        }

        foreach ($this->userProviderFactories as $userProvider) {
            $security->addUserProviderFactory($userProvider);
        }

        // load security services
        $security->createFirewalls($this->config, $builder);
        $security->createAuthorization($this->config, $builder);
        $roleDecision = $security->createRoleHierarchy($this->config, $builder);

        if (
            isset($this->config['access_decision_manager']['service']) &&
            $builder->hasDefinition($this->config['access_decision_manager']['service'])
        ) {
            $builder->addAlias($this->prefix('access.decision_manager'), $this->config['access_decision_manager']['service']);
        } else {
            $builder
                ->addDefinition($this->prefix('access.decision_manager'))
                ->setFactory(AccessDecisionManager::class)
                ->setArgument(0, $roleDecision)
                ->setArgument(1, $this->config['access_decision_manager']['strategy'])
                ->setArgument(2, $this->config['access_decision_manager']['allow_if_all_abstain'])
                ->setArgument(3, $this->config['access_decision_manager']['allow_if_equal_granted_denied'])
            ;
        }

        // Register other needed services for security
        $builder->addDefinition($this->prefix('untracked_token_storage'))->setFactory(TokenStorage::class);
        $builder->addDefinition($this->prefix('user_firewall'))->setFactory(UserFirewall::class);

        $builder->addDefinition($this->prefix('token_storage'))->setFactory(UsageTrackingTokenStorage::class)
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('authorization_checker'))->setFactory(AuthorizationChecker::class)
            ->setArgument(0, new Reference($this->prefix('token_storage')))
            ->setArgument(3, $builder->parameters[$this->name]['access']['always_authenticate_before_granting']);

        if (isset($this->config['encoders'])) {
            $security->createEncoders($this->config, $builder);

            $builder->addAlias($this->prefix('encoder_factory'), $this->prefix('encoder_factory.generic'));
        }

        // Commands
        $builder->addDefinition($this->prefix('command.user_password_encoder'))
            ->setFactory(UserPasswordEncoderCommand::class)
            ->addTag('console.command', $this->name . ':encode-password')
            ->setArgument(1, array_keys($this->config['encoders']));

        $userStatusCommand = $builder->addDefinition($this->prefix('command.user_status'))
            ->setFactory(UserStatusCommand::class)
            ->addTag('console.command', $this->name . ':user-status');

        if (count($builder->findByType(UserProviderInterface::class)) > 1) {
            $userStatusCommand->setArgument('provider', new Reference($this->prefix('user.provider.chain')));
        }
    }
}
