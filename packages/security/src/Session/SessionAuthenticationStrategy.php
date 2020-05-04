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

use BiuradPHP\Security\Interfaces\SessionAuthenticationStrategyInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The default session strategy implementation.
 *
 * Supports the following strategies:
 * NONE: the session is not changed
 * MIGRATE: the session id is updated, attributes are kept
 * INVALIDATE: the session id is updated, attributes are lost
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SessionAuthenticationStrategy implements SessionAuthenticationStrategyInterface
{
    const NONE = 'none';
    const MIGRATE = 'migrate';
    const INVALIDATE = 'invalidate';

    private $strategy;

    public function __construct(string $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthentication(Request $request, TokenInterface $token)
    {
        switch ($this->strategy) {
            case self::NONE:
                return;

            case self::MIGRATE:
                // Note: this logic is duplicated in several authentication listeners
                // until Symfony 5.0 due to a security fix with BC compat
                $request->getAttribute('session')->migrate(true);

                return;

            case self::INVALIDATE:
                $request->getAttribute('session')->invalidate();

                return;

            default:
                throw new RuntimeException(sprintf('Invalid session authentication strategy "%s"', $this->strategy));
        }
    }
}
