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
    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        return Nette\Schema\Expect::structure([
            'frames' => Expect::anyOf(Expect::string(), Expect::bool())->default('SAMEORIGIN'), // X-Frame-Options
            'emitters' => Expect::listOf(Expect::string()->assert('class_exists')),
			'content_security_policy' => Expect::arrayOf('array|scalar|null'), // Content-Security-Policy
			'cspReportOnly' => Expect::arrayOf('array|scalar|null'), // Content-Security-Policy-Report-Only
			'featurePolicy' => Expect::arrayOf('array|scalar|null'), // Feature-Policy
		])->otherItems('mixed');
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

        $response = $builder->addDefinition($this->prefix('response'))
            ->setType(\Psr\Http\Message\ResponseInterface::class)
            ->setFactory(BiuradPHP\Http\Response::class);

        $builder->addDefinition($this->prefix('csp'))
            ->setType(BiuradPHP\Http\Interfaces\CspInterface::class)
            ->setFactory(BiuradPHP\Http\Csp\ContentSecurityPolicy::class)
            ->setArguments([new Statement(BiuradPHP\Http\Csp\NonceGenerator::class)]);

        $builder->addDefinition($this->prefix('emitter'))
            ->setFactory(EmitterStack::class)
            ->addSetup('foreach (? as $emitter) { ?->push($this->createInstance($emitter)); }', [$this->config->emitters, '@self']);

        $this->resolveResponse($response);

        $builder->addAlias('emitter', $this->prefix('emitter'));
        $builder->addAlias('request', $this->prefix('request'));
        $builder->addAlias('response', $this->prefix('response'));
    }

    private function resolveResponse($response)
    {
        $config  = $this->config;
        $builder = $this->getContainerBuilder();
        $headers = [];
		$headers = array_map('strval', $headers);

		if (isset($config->frames) && $config->frames !== true) {
            $frames = $config->frames;

			if ($frames === false) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
            }

            $headers['X-Frame-Options'] = $frames;
        }

        $nonce = (new \BiuradPHP\Http\Csp\NonceGenerator())->generate();

		foreach (['content_security_policy', 'cspReportOnly'] as $key) {
			if (empty($config->$key)) {
				continue;
            }

            $value = self::buildPolicy($config->$key);

			//if (false !== strpos($value, 'nonce')) {
			//	$value = str_replace('nonce', 'nonce-' . $nonce, $value);
            //}

            if (true !== ($builder->parameters['access']['CONTENT_SECURITY_POLICY'] ?? false)) {
                break;
            }

			$headers['Content-Security-Policy' . ($key === 'content_security_policy' ? '' : '-Report-Only')] = $value;
		}

		if (!empty($config->featurePolicy)) {
			$headers['Feature-Policy'] = $this->buildPolicy($config->featurePolicy);
        }

        $responseHeaders = [];

		foreach ($headers as $key => $value) {
			if ($value !== '') {
                $responseHeaders += [$key => $value];
			}
        }

        $response->setArgument('body', 'php://memory');
        $response->setArgument('headers', $responseHeaders);
    }

    private function buildPolicy(array $config): string
	{
		static $nonQuoted = ['require-sri-for' => 1, 'sandbox' => 1];
        $value = '';

		foreach ($config as $type => $policy) {
			if ($policy === false) {
				continue;
            }

			$policy = $policy === true ? [] : (array) $policy;
            $value .= $type;

			foreach ($policy as $item) {
				$value .= !isset($nonQuoted[$type]) && preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
            }

			$value .= '; ';
		}
		return $value;
	}
}
