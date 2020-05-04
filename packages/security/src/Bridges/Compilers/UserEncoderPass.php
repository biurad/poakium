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

namespace BiuradPHP\Security\Bridges\Compilers;

use Nette\DI\Definitions\Reference;
use BiuradPHP\Security\Exceptions\InvalidConfigurationException;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder;

class UserEncoderPass
{
    public static function createEncoder($config)
    {
        // a custom encoder service
        if (isset($config['id'])) {
            return new Reference($config['id']);
        }

        if ($config['migrate_from'] ?? false) {
            return $config;
        }

        // plaintext encoder
        if ('plaintext' === $config['algorithm']) {
            $arguments = [$config['ignore_case']];

            return [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => $arguments,
            ];
        }

        // pbkdf2 encoder
        if ('pbkdf2' === $config['algorithm']) {
            return [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => [
                    $config['hash_algorithm'],
                    $config['encode_as_base64'],
                    $config['iterations'],
                    $config['key_length'],
                ],
            ];
        }

        // bcrypt encoder
        if ('bcrypt' === $config['algorithm']) {
            $config['algorithm'] = 'native';
            $config['native_algorithm'] = PASSWORD_BCRYPT;

            return self::createEncoder($config);
        }

        // Argon2i encoder
        if ('argon2i' === $config['algorithm']) {
            if (SodiumPasswordEncoder::isSupported() && !defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) {
                $config['algorithm'] = 'sodium';
            } elseif (defined('PASSWORD_ARGON2I')) {
                $config['algorithm'] = 'native';
                $config['native_algorithm'] = PASSWORD_ARGON2I;
            } else {
                throw new InvalidConfigurationException(sprintf('Algorithm "argon2i" is not available. Either use %s"auto" or upgrade to PHP 7.2+ instead.', defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13') ? '"argon2id", ' : ''));
            }

            return self::createEncoder($config);
        }

        if ('argon2id' === $config['algorithm']) {
            if (($hasSodium = SodiumPasswordEncoder::isSupported()) && defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) {
                $config['algorithm'] = 'sodium';
            } elseif (defined('PASSWORD_ARGON2ID')) {
                $config['algorithm'] = 'native';
                $config['native_algorithm'] = PASSWORD_ARGON2ID;
            } else {
                throw new InvalidConfigurationException(sprintf('Algorithm "argon2id" is not available. Either use %s"auto", upgrade to PHP 7.3+ or use libsodium 1.0.15+ instead.', defined('PASSWORD_ARGON2I') || $hasSodium ? '"argon2i", ' : ''));
            }

            return self::createEncoder($config);
        }

        if ('native' === $config['algorithm']) {
            return [
                'class' => NativePasswordEncoder::class,
                'arguments' => [
                    $config['time_cost'],
                    (($config['memory_cost'] ?? 0) << 10) ?: null,
                    $config['cost'],
                ] + (isset($config['native_algorithm']) ? [3 => $config['native_algorithm']] : []),
            ];
        }

        if ('sodium' === $config['algorithm']) {
            if (!SodiumPasswordEncoder::isSupported()) {
                throw new InvalidConfigurationException('Libsodium is not available. Install the sodium extension or use "auto" instead.');
            }

            return [
                'class' => SodiumPasswordEncoder::class,
                'arguments' => [
                    $config['time_cost'],
                    (($config['memory_cost'] ?? 0) << 10) ?: null,
                ],
            ];
        }

        // run-time configured encoder
        return $config;
    }
}
