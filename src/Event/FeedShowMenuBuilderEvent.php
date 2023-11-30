<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class FeedShowMenuBuilderEvent extends MenuBuilderEvent
{
    private FeedInterface $feed;

    public function __construct(FactoryInterface $factory, ItemInterface $menu, FeedInterface $feed)
    {
        parent::__construct($factory, $menu);

        $this->feed = $feed;
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }
}
