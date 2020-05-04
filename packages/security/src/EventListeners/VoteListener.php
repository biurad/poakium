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

namespace BiuradPHP\Security\EventListeners;

use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;
use Symfony\Component\Security\Core\Event\VoteEvent;

/**
 * Listen to vote events from traceable voters.
 *
 * @author Laurent VOULLEMIER <laurent.voullemier@gmail.com>
 *
 * @internal
 */
class VoteListener implements EventSubscriberInterface
{
    private $traceableAccessDecisionManager;

    public function __construct(TraceableAccessDecisionManager $traceableAccessDecisionManager)
    {
        $this->traceableAccessDecisionManager = $traceableAccessDecisionManager;
    }

    /**
     * Event dispatched by a voter during access manager decision.
     *
     * @param VoteEvent $event
     */
    public function onVoterVote(VoteEvent $event)
    {
        $this->traceableAccessDecisionManager->addVoterVote($event->getVoter(), $event->getAttributes(), $event->getVote());
    }

    public static function getSubscribedEvents(): array
    {
        return [VoteEvent::class => 'onVoterVote'];
    }
}
