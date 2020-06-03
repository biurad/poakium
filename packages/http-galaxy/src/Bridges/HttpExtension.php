<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Bridges;

use Nette, BiuradPHP;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;

class HttpExtension extends Nette\DI\CompilerExtension
{
    /** @var bool */
	private $debugMode;

	/** @var string|null */
	private $tempDir;


	public function __construct(bool $debugMode = false, string $tempDir = null)
	{
		$this->debugMode = $debugMode;
		$this->tempDir = $tempDir;
	}

    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        return Nette\Schema\Expect::structure([
            'caching' => Nette\Schema\Expect::structure([
                'debug'                     => Nette\Schema\Expect::bool()->default($this->debugMode),
                'default_ttl'               => Nette\Schema\Expect::int(),
                'private_headers'           => Nette\Schema\Expect::list(),
                'allow_reload'              => Nette\Schema\Expect::bool(),
                'allow_revalidate'          => Nette\Schema\Expect::bool(),
                'stale_while_revalidate'    => Nette\Schema\Expect::int(),
                'stale_if_error'            => Nette\Schema\Expect::int(),
                'surrogate'                 => Nette\Schema\Expect::anyOf('esi', 'ssi', null),
            ])->castTo('array'),
            'policies' => Nette\Schema\Expect::structure([
                'content_security_policy'   => Expect::array(), // Content-Security-Policy
                'csp_report_only'           => Expect::array(), // Content-Security-Policy-Report-Only
                'feature_policy'            => Expect::array(), // Feature-Policy
                'frame_policy'              => Expect::anyOf(Expect::string(), false)->default('SAMEORIGIN') // X-Frame-Options
                    ->before(function ($value) {
                        return null === $value ? '' : $value;
                    }),
            ])->castTo('array'),
            'headers' => Nette\Schema\Expect::structure([
                'cors' => Nette\Schema\Expect::structure([
                    'allowedPaths'       => Nette\Schema\Expect::list()
                        ->before(function ($value) {
                            return is_string($value) ? [$value] : $value;
                        }),
                    'allowedOrigins'     => Nette\Schema\Expect::list()
                        ->before(function ($value) {
                            return is_string($value) ? [$value] : $value;
                        }),
                    'allowedHeaders'    => Nette\Schema\Expect::list()
                        ->before(function ($value) {
                            return is_string($value) ? [$value] : $value;
                        }),
                    'allowedMethods'    => Nette\Schema\Expect::list()
                        ->before(function ($value) {
                            return is_string($value) ? [$value] : $value;
                        }),
                    'exposedHeaders'    => Nette\Schema\Expect::list()
                        ->before(function ($value) {
                            return is_string($value) ? [$value] : $value;
                        })->nullable(),
                    'allowCredentials'  => Nette\Schema\Expect::bool()->nullable(),
                    'maxAge'            => Nette\Schema\Expect::int()->nullable(),
                ])->castTo('array'),
                'request'               => Nette\Schema\Expect::array(),
                'response'              => Nette\Schema\Expect::array()
            ])->castTo('array'),
            'emitters' => Expect::listOf(Expect::string()->assert('class_exists')),
		])->castTo('array');
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
	{
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('factory'))
            ->setType(BiuradPHP\Http\Interfaces\Psr17Interface::class)
            ->setFactory(BiuradPHP\Http\Factories\GuzzleHttpPsr7Factory::class);

        $builder->addDefinition($this->prefix('pipeline'))
            ->setFactory(BiuradPHP\Http\Pipeline::class);

        $builder->addDefinition($this->prefix('request'))
            ->setType(\Psr\Http\Message\ServerRequestInterface::class)
			->setFactory(new Statement([new Reference($this->prefix('factory')), 'fromGlobalRequest']));

        $builder->addDefinition($this->prefix('response'))
            ->setType(\Psr\Http\Message\ResponseInterface::class)
            ->setFactory(BiuradPHP\Http\Response::class);

        $builder->addDefinition($this->prefix('access_control'))
            ->setFactory(BiuradPHP\Http\Cors\AccessControl::class, [$this->config['headers']['cors']]);

        $csPolicy = $builder->addDefinition($this->prefix('csp'))
            ->setType(BiuradPHP\Http\Interfaces\CspInterface::class)
            ->setFactory(BiuradPHP\Http\Csp\ContentSecurityPolicy::class)
            ->setArguments([new Statement(BiuradPHP\Http\Csp\NonceGenerator::class)]);

        if (false === ($builder->parameters['access']['CONTENT_SECURITY_POLICY'] ?? true)) {
            $csPolicy->addSetup('disableCsp');
        }

        $builder->addDefinition($this->prefix('http_middleware'))
            ->setFactory(BiuradPHP\Http\Middlewares\HttpMiddleware::class, [$this->config]);

        if (class_exists(BiuradPHP\HttpCache\HttpCache::class) && class_exists(BiuradPHP\Routing\Bridges\RoutingExtension::class)) {
            $surrogate = null;
            if ('esi' === $this->config['caching']['surrogate']) {
                $surrogate = new Statement(BiuradPHP\HttpCache\Esi::class);
            } elseif ('ssi' === $this->config['caching']['surrogate']) {
                $surrogate = new Statement(BiuradPHP\HttpCache\Ssi::class);
            }
            unset($this->config['caching']['surrogate']);

            $builder->addDefinition($this->prefix('cache'))
                ->setFactory(BiuradPHP\HttpCache\HttpCache::class)
                ->setArgument('store', new Statement(BiuradPHP\HttpCache\Store::class, [$this->tempDir]))
                ->setArgument('options', $this->config['caching'])
                ->setArgument('surrogate', $surrogate);
        }

        $builder->addDefinition($this->prefix('emitter'))
            ->setFactory(EmitterStack::class)
            ->addSetup('foreach (? as $emitter) { ?->push($this->createInstance($emitter)); }', [$this->config['emitters'], '@self']);

        $builder->addAlias('emitter', $this->prefix('emitter'));
        $builder->addAlias('request', $this->prefix('request'));
        $builder->addAlias('response', $this->prefix('response'));
    }
}
