<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $catalog = $menu->getChild('catalog');
        if ($catalog) {
            $catalog->addChild('feeds', [
                'route' => 'setono_sylius_feed_admin_feed_index',
            ])
                ->setLabel('Feeds')
                ->setLabelAttribute('icon', 'table')
            ;
        }
    }
}
