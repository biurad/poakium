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

namespace BiuradPHP\Security\Event;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Deauthentication happens in case the user has changed when trying to refresh the token.
 *
 * @author Hamza Amrouche <hamza.simperfit@gmail.com>
 */
final class DeauthenticatedEvent extends Event
{
    private $originalToken;
    private $refreshedToken;

    public function __construct(TokenInterface $originalToken, TokenInterface $refreshedToken)
    {
        $this->originalToken = $originalToken;
        $this->refreshedToken = $refreshedToken;
    }

    public function getRefreshedToken(): TokenInterface
    {
        return $this->refreshedToken;
    }

    public function getOriginalToken(): TokenInterface
    {
        return $this->originalToken;
    }
}
