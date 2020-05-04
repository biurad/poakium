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

namespace BiuradPHP\Security;

use BiuradPHP\Security\Event;

final class SecurityEvents
{
    /**
     * The INTERACTIVE_LOGIN event occurs after a user has actively logged
     * into your website. It is important to distinguish this action from
     * non-interactive authentication methods, such as:
     *   - authentication based on your session.
     *   - authentication using a HTTP basic or HTTP digest header.
     */
    public const INTERACTIVE_LOGIN = Event\InteractiveLoginEvent::class;

    /**
     * The SWITCH_USER event occurs before switch to another user and
     * before exit from an already switched user.
     */
    public const SWITCH_USER = Event\SwitchUserEvent::class;

    /**
     * The LOGIN_RESPONSE event is what is processed duringa user's,
     * authetication and authorization, This event actions occurs
     * in middlewares process, so developers can add, or
     * modify the response or request. similar to INTERACTIVE_LOGIN
     */
    public const LOGIN_RESPONSE = Event\LoginEvent::class;

    /**
     * Occurs when the user is logged in programmatically.
     */
    public const IMPLICIT_LOGIN = Event\UserEvent::class;

    /**
     * Occurs when a new user is registered.
     */
    public const NEW_USER = Event\RegisteredEvent::class;
}
