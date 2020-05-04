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

namespace BiuradPHP\Security\Concerns;

use BiuradPHP\Session\Interfaces\SessionInterface;

/**
 * Trait to get (and set) the URL the user last visited before being forced to authenticate.
 */
trait TargetPathTrait
{
    /**
     * Sets the target path the user should be redirected to after authentication.
     *
     * Usually, you do not need to set this directly.
     * 
     * @param SessionInterface $session
     * @param string $providerKey
     * @param string $uri
     */
    private function saveTargetPath(SessionInterface $session, string $providerKey, string $uri)
    {
        $session->getSection()->set('_security.'.$providerKey.'.target_path', $uri);
    }

    /**
     * Returns the URL (if any) the user visited that forced them to login.
     * 
     * @param SessionInterface $session
     * @param string $providerKey
     * 
     * @return string|null
     */
    private function getTargetPath(SessionInterface $session, string $providerKey): ?string
    {
        return $session->getSection()->get('_security.'.$providerKey.'.target_path');
    }

    /**
     * Removes the target path from the session.
     * 
     * @param SessionInterface $session
     * @param string $providerKey
     */
    private function removeTargetPath(SessionInterface $session, string $providerKey)
    {
        $session->getSection()->delete('_security.'.$providerKey.'.target_path');
    }
}
