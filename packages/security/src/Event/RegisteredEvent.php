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

use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class RegisteredEvent extends UserEvent
{
    private $request;

    public function __construct(Request $request, UserInterface $user)
    {
        $this->request = $request;
        parent::__construct($user);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
