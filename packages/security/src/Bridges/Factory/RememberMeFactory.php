<?php /** @noinspection PhpExpressionResultUnusedInspection */

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

use RuntimeException;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use BiuradPHP\Security\Firewalls\RememberMeListener;
use BiuradPHP\Security\Interfaces\SecurityFactoryInterface;
use BiuradPHP\Security\RememberMe\PersistentTokenBasedRememberMeServices;
use BiuradPHP\Security\RememberMe\TokenBasedRememberMeServices;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;

class RememberMeFactory implements SecurityFactoryInterface
{
    protected $options = [
        'name' => 'REMEMBERME',
        'lifetime' => 31536000,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
        'samesite' => null,
        'always_remember_me' => false,
        'remember_me_parameter' => '_remember_me',
    ];

    public function create(ContainerBuilder $container, string $id, array $config, ?string $userProvider)
    {
        // Fix secret for remember me using application's salt.
        if (null === $config['secret']) {
            $config['secret'] = $container->parameters['env']['SALT'] ?? random_bytes(16);
        }

        // authentication provider
        $authProviderId = 'security.authentication.provider.rememberme.'.$id;
        $container
            ->addDefinition($authProviderId)->setFactory(RememberMeAuthenticationProvider::class)
            ->setArgument(0, new Reference('security.user_checker.'.$id))
            ->setArgument(1, $config['secret'])
            ->setArgument(2, $id)
            ->setAutowired(false)
        ;

        // remember me services
        if (isset($config['token_provider'])) {
            $templateId = 'security.authentication.rememberme.services.persistent';
            $rememberMeServicesId = $templateId.'.'.$id;
            $template = PersistentTokenBasedRememberMeServices::class;
        } else {
            $templateId = 'security.authentication.rememberme.services.simplehash';
            $rememberMeServicesId = $templateId.'.'.$id;
            $template = TokenBasedRememberMeServices::class;
        }

        $rememberMeServices = $container->addDefinition($rememberMeServicesId)->setFactory(new Statement($template));
        $rememberMeServices->setArgument(1, $config['secret']);
        $rememberMeServices->setArgument(2, $id);

        if (isset($config['token_provider'])) {
            $rememberMeServices->addSetup('setTokenProvider', [new Reference($config['token_provider'])]);
        }

        if ($container->hasDefinition('security.logout_listener.'.$id)) {
            $container
                ->getDefinition('security.logout_listener.'.$id)
                ->addSetup('addHandler', [new Reference($rememberMeServicesId)])
            ;
        }

        // remember-me options
        $rememberMeServices->setArgument(3, array_intersect_key($config, $this->options));

        // attach to remember-me aware listeners
        $userProviders = [];
        foreach ($container->findByTag('security.remember_me_aware') as $serviceId => $attribute) {
            if (!isset($attribute['id']) || $attribute['id'] !== $id) {
                continue;
            }

            if (!isset($attribute['provider'])) {
                throw new RuntimeException('Each "security.remember_me_aware" tag must have a provider attribute.');
            }

            // context listeners don't need a provider
            if ('none' !== $attribute['provider']) {
                $userProviders[] = new Reference($attribute['provider']);
            }

            $container
                ->getDefinition($serviceId)
                ->addSetup('setRememberMeServices', [new Reference($rememberMeServicesId)])
            ;
        }
        if ($config['user_providers']) {
            $userProviders = [];
            foreach ($config['user_providers'] as $providerName) {
                $userProviders[] = new Reference('security.user.provider.concrete.'.$providerName);
            }
        }

        if (0 === count($userProviders)) {
            throw new RuntimeException('You must configure at least one remember-me aware listener (such as form-login) for each firewall that has remember-me enabled.');
        }

        $rememberMeServices->setArgument(0, array_unique($userProviders));

        // remember-me listener
        $listenerId = 'security.authentication.listener.rememberme.'.$id;
        $listener = $container->addDefinition($listenerId)->setFactory(RememberMeListener::class);
        $listener->setArgument(1, new Reference($rememberMeServicesId));
        $listener->setArgument(5, $config['catch_exceptions']);

        return [$authProviderId, $listenerId];
    }

    public function getPosition()
    {
        return 'remember_me';
    }

    public function getKey()
    {
        return 'remember-me';
    }

    /**
     * @param \Nette\Schema\Expect $node
     *
     * @return \Nette\Schema\Elements\Structure
     */
    public function addConfiguration(\Nette\Schema\Expect $node)
    {
        $configurations = [];
        foreach ($this->options as $name => $value) {
            if ('secure' === $name) {
                $configurations[$name] = $node::anyOf(true, false, 'auto')->default('auto' === $value ? null : $value);
            } elseif ('samesite' === $name) {
                $configurations[$name] = $node::anyOf(null, 'lax', 'strict', 'none')->default($value);
            } elseif (is_bool($value)) {
                $configurations[$name] = $node::bool()->default($value);
            } else {
                $configurations[$name] = $node::string()->default($value);
            }
        }

        $defaults = [
            'secret' => $node::string(),
            'token_provider' => $node::string(),
            'user_providers' => $node::list()->before(function ($value) {
                return is_string($value) ? [$value] : $value;
            }),
            'catch_exceptions' => $node::bool()->default(true),
        ];

        return $node::structure(array_merge($defaults, $configurations))->castTo('array');
    }
}
