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

namespace BiuradPHP\Security\Session;

use BiuradPHP\Session\Interfaces\SessionInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

 /**
 * Token storage that uses Array session handling.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CsrfTokenStorage implements ClearableTokenStorageInterface
{
    /**
     * The namespace used to store values in the session.
     */
    const SESSION_NAMESPACE = '_csrf';

    private $session;
    private $namespace;

    /**
     * Initializes the storage with a Session object and a session namespace.
     *
     * @param SessionInterface $session
     * @param string $namespace The namespace under which the token is stored in the session
     */
    public function __construct(SessionInterface $session, string $namespace = self::SESSION_NAMESPACE)
    {
        $this->session = $session;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        if (!$this->session->getSection()->has($this->namespace.'/'.$tokenId)) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $this->session->getSection()->get($this->namespace.'/'.$tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->session->getSection()->set($this->namespace.'/'.$tokenId, (string) $token);
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->getSection()->has($this->namespace.'/'.$tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->getSection()->delete($this->namespace.'/'.$tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach (array_keys($this->session->getSection()->getAll()) as $key) {
            if (0 === strpos($key, $this->namespace.'/')) {
                $this->session->getSection()->delete($key);
            }
        }
    }
}
