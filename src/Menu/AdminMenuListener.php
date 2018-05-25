<?php

namespace Loevgaard\SyliusFeedPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $menu
            ->getChild('catalog')
            ->addChild('feeds', [
                'route' => 'loevgaard_sylius_feed_admin_feed_index'
            ])
            ->setLabel('Feeds')
            ->setLabelAttribute('icon', 'table')
        ;
    }
}