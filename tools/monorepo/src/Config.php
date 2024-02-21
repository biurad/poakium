<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Monorepo;

use Symfony\Component\OptionsResolver\Exception\{AccessException, InvalidOptionsException};
use Symfony\Component\OptionsResolver\{Options, OptionsResolver};
use Symfony\Component\Yaml\Yaml;

/**
 * Monorepo configuration (.monorepo).
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class Config implements \ArrayAccess, \Countable
{
    /** @var array<string,mixed> */
    private array $config;

    public function __construct(string $rootPath, string $cachePath = null, bool $reclone)
    {
        if (!\file_exists($configFile = ($this->config['path'] = $rootPath).'/.monorepo')) {
            throw new \RuntimeException(\sprintf('Config file "%s" does not exist.', $configFile));
        }

        $workers = [];
        $this->config['reclone'] = $reclone;
        $options = new OptionsResolver();
        $options
            ->define('base_url')->default(null)->allowedTypes('string', 'null')
            ->define('branch_filter')->default(null)->allowedTypes('string', 'null')
            ->define('extra')->default([])->allowedTypes('array')
            ->define('workers')->default([])->allowedTypes('array')
            ->normalize(function (Options $options, array $value): array {
                if (\array_is_list($value)) {
                    return ['main' => $value];
                }

                foreach ($value as $k => $v) {
                    $v = \is_string($v) ? [$v] : $v;

                    if (!\is_string($k)) {
                        throw new InvalidOptionsException('Expected workers config to begin with a string key.');
                    }

                    if (!\is_array($v) || !\array_is_list($v)) {
                        throw new InvalidOptionsException('Expected workers config\'s key value be a list of workers classes.');
                    }
                }

                return $value;
            })
            ->define('repositories')
            ->default(function (OptionsResolver $options, Options $parent) use (&$workers): void {
                $workers = \array_keys($parent['workers']);
                $options->setPrototype(true)
                    ->define('url')->default(null)->allowedTypes('string', 'null')->required()
                    ->define('merge')->default(false)->allowedTypes('bool')
                    ->define('path')
                    ->allowedTypes('string')
                    ->default(\Closure::bind(fn (Options $options): string => $options->prototypeIndex, null, $options))
                    ->normalize(fn (Options $options, string $value): string => \trim($value, '/'))
                    ->define('workers')->default($workers)->allowedTypes('array')
                    ->normalize(function (Options $options, array $value) use ($workers): array {
                        foreach ($value as $worker) {
                            if (!\in_array($worker, $workers, true)) {
                                throw new InvalidOptionsException(\sprintf('The worker "%s" for monorepo\'s path "%s" does not exist.', $worker, $options['path']));
                            }
                        }

                        return $value;
                    })
                ;
            })->allowedTypes('array')
        ;

        $options->setRequired(['base_url', 'branch_filter', 'workers', 'repositories']);
        $this->config += $options->resolve(\function_exists('yaml_parse_file') ? yaml_parse_file($configFile) : Yaml::parseFile($configFile));
        $this->config['cache'] = \realpath($cachePath ?? $this->config['path'].'/.monorepo-cache');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!\is_string($offset)) {
            throw new InvalidOptionsException(\sprintf('Expected a string key, got a type of "%s" instead.', \get_debug_type($offset)));
        }

        return $this->config[$offset] ?? throw new InvalidOptionsException(\sprintf('Value for "%s" does not exist.', $offset));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!\is_string($offset)) {
            throw new InvalidOptionsException(\sprintf('Expected a string key, got a type of "%s" instead.', \get_debug_type($offset)));
        }

        return \array_key_exists($offset, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new AccessException('Unexpected call for setting config, kindly define config in the .monorepo file.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!\is_string($offset)) {
            throw new InvalidOptionsException(\sprintf('Expected a string key, got a type of "%s" instead.', \get_debug_type($offset)));
        }

        unset($this->config[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->config);
    }
}
