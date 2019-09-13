<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

final class StartProcessingSubscriber implements EventSubscriberInterface
{
    /**
     * @throws StringsException
     */
    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_PROCESS);

        return [
            $event => 'start',
        ];
    }

    public function start(TransitionEvent $event): void
    {
        $feed = $event->getSubject();

        if (!$feed instanceof FeedInterface) {
            return;
        }

        $feed->resetBatches();
        $feed->clearViolations();
    }
}
