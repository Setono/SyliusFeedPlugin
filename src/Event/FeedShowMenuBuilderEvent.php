<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class FeedShowMenuBuilderEvent extends MenuBuilderEvent
{
    public function __construct(
        FactoryInterface $factory,
        ItemInterface $menu,
        private readonly FeedInterface $feed,
    ) {
        parent::__construct($factory, $menu);
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }
}
