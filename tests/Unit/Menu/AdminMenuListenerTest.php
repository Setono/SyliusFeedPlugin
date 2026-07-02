<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Menu;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Menu\AdminMenuListener;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListenerTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_a_feeds_item_under_the_catalog_section(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');

        (new AdminMenuListener())->addAdminMenuItems(new MenuBuilderEvent($factory, $menu));

        $catalog = $menu->getChild('catalog');
        self::assertNotNull($catalog);

        $feeds = $catalog->getChild('setono_sylius_feed_feeds');
        self::assertNotNull($feeds);
        self::assertSame('setono_sylius_feed.ui.feeds', $feeds->getLabel());
    }
}
