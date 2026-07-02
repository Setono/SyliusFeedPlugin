<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * Adds a "Feeds" item to the Sylius admin menu, under the existing "Catalog" section (§13).
 */
final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $catalog = $menu->getChild('catalog');
        if (null === $catalog) {
            $catalog = $menu
                ->addChild('catalog')
                ->setLabel('sylius.menu.admin.main.catalog.header')
            ;
        }

        $catalog
            ->addChild('setono_sylius_feed_feeds', ['route' => 'setono_sylius_feed_admin_feed_index'])
            ->setLabel('setono_sylius_feed.ui.feeds')
            ->setLabelAttribute('icon', 'rss')
        ;
    }
}
