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

namespace BiuradPHP\Security\Interfaces;

use BiuradPHP\Security\Exceptions\DecryptException;
use BiuradPHP\Security\Exceptions\EncryptException;

interface EncrypterInterface
{
    /**
     * Encrypt the given value.
     *
     * @param mixed $value
     * @param bool  $serialize
     *
     * @return string
     *
     * @throws EncryptException
     */
    public function encrypt($value, $serialize = true);

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     * @param bool   $unserialize
     *
     * @return mixed
     *
     * @throws DecryptException
     */
    public function decrypt($payload, $unserialize = true);

    /**
     * Encrypt a string without serialization.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws EncryptException
     */
    public function encryptString($value);

    /**
     * Decrypt the given string without unserialization.
     *
     * @param string $payload
     *
     * @return string
     *
     * @throws DecryptException
     */
    public function decryptString($payload);

    /**
     * Get the encryption key.
     *
     * @return string
     */
    public function getKey();
}
