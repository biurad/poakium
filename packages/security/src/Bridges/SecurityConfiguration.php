<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpStrictTypeCheckingInspection */

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

use InvalidArgumentException;
use Nette, BiuradPHP;
use Nette\Schema\Expect;
use BiuradPHP\Security\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

/**
 * SecurityExtension configuration structure.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecurityConfiguration
{
    private $factories;
    private $userProviderFactories;

    public function __construct(array $factories, array $userProviderFactories)
    {
        $this->factories = $factories;
        $this->userProviderFactories = $userProviderFactories;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return Nette\Schema\Schema The tree builder
     */
    public function getConfigTree(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'access_denied_url'                     => Nette\Schema\Expect::string()->default(null),
            'session_fixation_strategy'             => Expect::anyOf(
                SessionAuthenticationStrategy::NONE, SessionAuthenticationStrategy::MIGRATE, SessionAuthenticationStrategy::INVALIDATE
                )->default(SessionAuthenticationStrategy::MIGRATE),
            'hide_user_not_found'                   => Nette\Schema\Expect::bool()->default(true),
            'always_authenticate_before_granting'   => Nette\Schema\Expect::bool()->default(false),
            'erase_credentials'                     => Nette\Schema\Expect::bool()->default(true),
            'access_decision_manager'               => Nette\Schema\Expect::structure([
                'strategy'                          => Expect::anyOf(
                    AccessDecisionManager::STRATEGY_AFFIRMATIVE, AccessDecisionManager::STRATEGY_CONSENSUS, AccessDecisionManager::STRATEGY_UNANIMOUS
                    )->default(AccessDecisionManager::STRATEGY_AFFIRMATIVE),
                'service'                           => Nette\Schema\Expect::string(),
                'allow_if_all_abstain'              => Nette\Schema\Expect::bool()->default(false),
                'allow_if_equal_granted_denied'     => Nette\Schema\Expect::bool()->default(true),
            ])->castTo('array'),
            'encryption'                            => $this->addEncryptionSection(),
            'encoders'                              => $this->addEncodersSection(),
            'providers'                             => $this->addProvidersSection(),
            'firewalls'                             => $this->addFirewallsSection($this->factories),
            'access_control'                        => $this->addAccessControlSection(),
            'role_hierarchy'                        => $this->addRoleHierarchySection(),
            'passwords'                             => Nette\Schema\Expect::structure([
                'options'           => Nette\Schema\Expect::structure([
                    'length'            => Nette\Schema\Expect::int()->min(4)->default(8),
                    'max_similarity'    => Nette\Schema\Expect::int()->min(20)->default(70),
                    'reset_time'        => Nette\Schema\Expect::int(3600)
                ])->otherItems('array')->castTo('array'),
                'validators'        => Expect::array()->items(
                    Expect::anyOf(Expect::string()->assert('class_exists'), Expect::object()->assert('get_class'))
                ),
            ])->castTo('array'),
		])->castTo('array');
    }

    private function addEncryptionSection()
    {
        return Nette\Schema\Expect::structure([
            'secret'    => Nette\Schema\Expect::string(),
            'cipher'    => Nette\Schema\Expect::anyOf('AES-128-CBC', 'AES-256-CBC')
        ])->castTo('array');
    }

    private function addRoleHierarchySection()
    {
        return Nette\Schema\Expect::array()
            ->items(Expect::array()->items('string')->before(function ($value) {
                if (is_string($value) && strpos($value, 'ROLE_') !== 0) {
                    throw new InvalidArgumentException('Expected role names to be prefixed with "ROLE_');
                }

                return is_string($value) ? [$value] : $value;
            }))->castTo('array');
    }

    private function addEncodersSection()
    {
        return Nette\Schema\Expect::array()->items(
            Nette\Schema\Expect::structure([
                'algorithm'         => Nette\Schema\Expect::string(),
                'migrate_from'      => Nette\Schema\Expect::array(),
                'hash_algorithm'    => Nette\Schema\Expect::string('sha512'),
                'key_length'        => Nette\Schema\Expect::int(40),
                'ignore_case'       => Nette\Schema\Expect::bool(false),
                'encode_as_base64'  => Nette\Schema\Expect::bool(true),
                'iterations'        => Nette\Schema\Expect::int(5000),
                'cost'              => Nette\Schema\Expect::int()->min(4)->max(31),
                'memory_cost'       => Nette\Schema\Expect::int(),
                'time_cost'         => Nette\Schema\Expect::int(),
                'id'                => Nette\Schema\Expect::string(),
                'class'             => Nette\Schema\Expect::string()->assert('class_exists'),
            ])->before(function ($value) {
                return is_string($value) ? ['algorithm' => $value] : $value;
            }
        )->castTo('array'));
    }

    private function addProvidersSection()
    {
        $providers = [];
        foreach ($this->userProviderFactories as $factory) {
            $name = str_replace('-', '_', $factory->getKey());
            $providers[$name] = Expect::structure([
                $name => $factory->addConfiguration(new Expect())
            ])->castTo('array');
        }

        return Nette\Schema\Expect::array()->before(function ($values) use ($providers) {
            $validated = [];
            foreach ($values as $value => $attributes) {
                $defaultSchema = $providers[key($attributes)];

                if (array_key_exists('id', $attributes)) {
                    $defaultSchema = Expect::structure([
                        'id' => Nette\Schema\Expect::string()->nullable(),
                    ])->castTo('array');
                }

                if (null === $defaultSchema) {
                    continue;
                }

                $validated[$value] = (new Nette\Schema\Processor)->process($defaultSchema, $attributes);
            }

            return $validated;
        })->castTo('array');
    }

    private function addFirewallsSection(array $factories)
    {
        $factoryKeys = [];
        foreach ($factories as $factory) {
            $name = str_replace('-', '_', $factory->getKey());
            $factoryKeys[$name] = $factory->addConfiguration(new Expect());
        }

        return Nette\Schema\Expect::arrayOf(
            Nette\Schema\Expect::structure(array_merge([
                'pattern'           => Nette\Schema\Expect::string(),
                'host'              => Nette\Schema\Expect::string(),
                'methods'           => Nette\Schema\Expect::listOf('string')->before(function ($value) {
                    return is_string($value) ? [$value] : $value;
                }),
                'security'          => Nette\Schema\Expect::bool(true),
                'user_checker'      => Nette\Schema\Expect::string('security.user_checker'),
                'request_matcher'   => Nette\Schema\Expect::string(),
                'access_denied_url' => Nette\Schema\Expect::string(),
                'stateless'         => Nette\Schema\Expect::bool(false),
                'context'           => Nette\Schema\Expect::string(),
                'provider'          => Nette\Schema\Expect::string(),
                'logout'            => Nette\Schema\Expect::structure([
                    'csrf_parameter'        => Nette\Schema\Expect::string('_csrf_token'),
                    'csrf_token_id'         => Nette\Schema\Expect::string('logout'),
                    'csrf_status'           => Nette\Schema\Expect::bool(true),
                    'path'                  => Nette\Schema\Expect::string('./logout'),
                    'target'                => Nette\Schema\Expect::string('./'),
                    'invalidate_session'    => Nette\Schema\Expect::bool(true),
                    'delete_cookies'        => Nette\Schema\Expect::array()->items(
                        Expect::structure([
                            'path'      => Nette\Schema\Expect::string()->nullable(),
                            'domain'    => Nette\Schema\Expect::string()->nullable()
                        ])->castTo('array')),
                    'handlers'              => Nette\Schema\Expect::list()
                ])->castTo('array'),
                'switch_user'   => Nette\Schema\Expect::anyOf(
                    Expect::null(),
                    Nette\Schema\Expect::structure([
                        'provider'      => Nette\Schema\Expect::string(),
                        'parameter'     => Nette\Schema\Expect::string('_switch_user'),
                        'role'          => Nette\Schema\Expect::string('ROLE_ALLOWED_TO_SWITCH')
                    ])->before(function ($value) {
                        return (is_bool($value) && true === $value) ? [] : $value;
                    })->castTo('array')
                )->default(null),
            ], $factoryKeys))->otherItems('array')
        ->castTo('array'));
    }

    private function addAccessControlSection()
    {
        return Nette\Schema\Expect::array()->items(
            Expect::structure([
                'requires_channel'  => Nette\Schema\Expect::string(),
                'path'              => Nette\Schema\Expect::string(),
                'host'              => Nette\Schema\Expect::string(),
                'port'              => Nette\Schema\Expect::string(),
                'ips'               => Nette\Schema\Expect::list(),
                'methods'           => Nette\Schema\Expect::listOf('string'),
                'allow_if'          => Nette\Schema\Expect::string(),
                'roles'             => Nette\Schema\Expect::listOf('string')->before(function ($value) {
                    return is_string($value) ? [$value] : $value;
                }),
            ])->castTo('array')
        );
    }
}
