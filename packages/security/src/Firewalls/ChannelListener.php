<?php /** @noinspection PhpUndefinedMethodInspection */

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

use BiuradPHP\Http\Response\RedirectResponse;
use BiuradPHP\Security\Event\LoginEvent as RequestEvent;
use BiuradPHP\Security\Interfaces\AccessMapInterface;
use Psr\Log\LoggerInterface;

/**
 * ChannelListener switches the HTTP protocol based on the access control
 * configuration.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class ChannelListener extends AbstractListener
{
    private $map;
    private $logger;

    public function __construct(AccessMapInterface $map, LoggerInterface $logger = null)
    {
        $this->map = $map;
        $this->logger = $logger;
    }

    /**
     * Handles channel management.
     * @param RequestEvent $event
     * @return bool|null
     */
    public function supports(RequestEvent $event): ?bool
    {
        $request = $event->getRequest();

        [, $channel] = $this->map->getPatterns($request);

        if ('https' === $channel && !$request->isSecure()) {
            if (null !== $this->logger) {
                if ('https' === $request->getHeaderLine('X-Forwarded-Proto')) {
                    $this->logger->info('Redirecting to HTTPS. ("X-Forwarded-Proto" header is set to "https" - did you set "trusted_proxies" correctly?)');
                } elseif (false !== strpos($request->getHeaderLine('Forwarded'), 'proto=https')) {
                    $this->logger->info('Redirecting to HTTPS. ("Forwarded" header is set to "proto=https" - did you set "trusted_proxies" correctly?)');
                } else {
                    $this->logger->info('Redirecting to HTTPS.');
                }
            }

            return true;
        }

        if ('http' === $channel && $request->isSecure()) {
            if (null !== $this->logger) {
                $this->logger->info('Redirecting to HTTP.');
            }

            return true;
        }

        return false;
    }

    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();
        $uriPath = $request->getUri();
        [, $channel] = $this->map->getPatterns($request);

        if ('https' === $channel && !$request->isSecure()) {
            $uriPath = $uriPath->withScheme('https');
        }
        if ('http' === $channel && $request->isSecure()) {
            $uriPath = $uriPath->withScheme('http');
        }

        $event->setResponse(new RedirectResponse($uriPath, 301));
    }
}
