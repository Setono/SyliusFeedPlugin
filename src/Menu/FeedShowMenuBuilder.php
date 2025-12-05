<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusFeedPlugin\Event\FeedShowMenuBuilderEvent;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\Workflow\Registry;

final class FeedShowMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Registry $workflowRegistry,
    ) {
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        if (!isset($options['feed'])) {
            return $menu;
        }

        $feed = $options['feed'];

        if (!$feed instanceof FeedInterface) {
            return $menu;
        }

        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if ($workflow->can($feed, FeedGraph::TRANSITION_PROCESS)) {
            $menu
                ->addChild('generate', [
                    'route' => 'setono_sylius_feed_admin_feed_process',
                    'routeParameters' => ['id' => $feed->getId()],
                ])
                ->setAttribute('type', 'link')
                ->setLabel('setono_sylius_feed.ui.generate_feed')
                ->setLabelAttribute('icon', 'redo');
        }

        $menu
            ->addChild('edit', [
                'route' => 'setono_sylius_feed_admin_feed_update',
                'routeParameters' => ['id' => $feed->getId()],
            ])
            ->setAttribute('type', 'link')
            ->setLabel('setono_sylius_feed.ui.edit_feed')
            ->setLabelAttribute('icon', 'pencil');

        $this->eventDispatcher->dispatch(new FeedShowMenuBuilderEvent($this->factory, $menu, $feed));

        return $menu;
    }
}
