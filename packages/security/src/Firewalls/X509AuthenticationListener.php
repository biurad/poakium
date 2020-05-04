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

namespace BiuradPHP\Security\Firewalls;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * X509 authentication listener.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class X509AuthenticationListener extends AbstractPreAuthenticatedListener
{
    private $userKey;
    private $credentialKey;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, string $providerKey, string $userKey = 'SSL_CLIENT_S_DN_Email', string $credentialKey = 'SSL_CLIENT_S_DN', LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($tokenStorage, $authenticationManager, $providerKey, $logger, $dispatcher);

        $this->userKey = $userKey;
        $this->credentialKey = $credentialKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreAuthenticatedData(Request $request)
    {
        $user = null;
        $matches = [];
        $servers = $request->getServerParams();

        if (isset($servers[$this->userKey])) {
            $user = $servers[$this->userKey];
        } elseif (
            isset($servers[$this->credentialKey])
            && preg_match('#emailAddress=([^,/@]++@[^,/]++)#', $servers[$this->credentialKey], $matches)
        ) {
            $user = $matches[1];
        }

        if (null === $user) {
            throw new BadCredentialsException(sprintf('SSL credentials not found: %s, %s', $this->userKey, $this->credentialKey));
        }

        return [$user, $servers[$this->credentialKey] ?? ''];
    }
}
