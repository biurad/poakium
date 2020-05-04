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

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};

/**
 * This is used by the ExceptionListener to translate an AccessDeniedException
 * to a Response object.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AccessDeniedHandlerInterface
{
    /**
     * Handles an access denied failure.
     *
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     * 
     * @return Response|null
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException);
}
