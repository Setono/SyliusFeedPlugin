<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddAdminMenuSubscriber implements EventSubscriberInterface
{
    public const MENU_KEY = 'setono_sylius_feed__feeds';

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'add',
        ];
    }

    public function add(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $section = $menu->getChild('catalog');
        if (null === $section) {
            return;
        }

        $section
            ->addChild(self::MENU_KEY, [
                'route' => 'setono_sylius_feed_admin_feed_index',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('setono_sylius_feed.menu.admin.main.catalog.feeds')
            ->setLabelAttributes([
                'icon' => 'rss',
            ])
        ;
    }
}
