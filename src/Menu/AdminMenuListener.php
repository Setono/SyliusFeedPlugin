<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $catalog = $menu->getChild('catalog');
        if (null !== $catalog) {
            $catalog->addChild('feeds', [
                'route' => 'setono_sylius_feed_admin_feed_index',
            ])
                ->setLabel('setono_sylius_feed.ui.feeds')
                ->setLabelAttribute('icon', 'table')
            ;
        }
    }
}
