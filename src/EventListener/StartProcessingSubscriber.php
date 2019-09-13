<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

final class StartProcessingSubscriber implements EventSubscriberInterface
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
    }

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

        if (!$this->feedTypeRegistry->has($feed->getFeedType())) {
            return;
        }

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());
        $dataProvider = $feedType->getDataProvider();

        $batchCount = 0;
        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $batchCount += $dataProvider->getBatchCount($channel, $locale);
            }
        }

        $feed->resetBatches();
        $feed->setBatches($batchCount);
        $feed->clearViolations();
    }
}
