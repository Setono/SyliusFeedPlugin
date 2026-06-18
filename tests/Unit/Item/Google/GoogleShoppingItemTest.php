<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Item\Google;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\Google\Availability;
use Setono\SyliusFeedPlugin\Item\Google\Condition;
use Setono\SyliusFeedPlugin\Item\Google\GoogleShoppingItem;

/**
 * @covers \Setono\SyliusFeedPlugin\Item\Google\GoogleShoppingItem
 */
final class GoogleShoppingItemTest extends TestCase
{
    private function createItem(): GoogleShoppingItem
    {
        return new GoogleShoppingItem(new \stdClass(), new FeedContext());
    }

    /**
     * @test
     */
    public function it_reads_and_writes_typed_string_fields_through_the_shared_bag(): void
    {
        $item = $this->createItem();
        $item->setId('SKU-1');
        $item->setTitle('Acme Shoe');

        self::assertSame('SKU-1', $item->getId());
        self::assertSame('Acme Shoe', $item->getTitle());

        // the typed accessors and the generic bag are the same source of truth
        self::assertSame('SKU-1', $item->get(GoogleShoppingItem::ID));
        self::assertSame(['g:id' => 'SKU-1', 'g:title' => 'Acme Shoe'], $item->all());
    }

    /**
     * @test
     */
    public function it_stores_enum_values_as_scalars_and_reads_them_back_as_enums(): void
    {
        $item = $this->createItem();
        $item->setAvailability(Availability::IN_STOCK);
        $item->setCondition(Condition::NEW);

        self::assertSame('in_stock', $item->get(GoogleShoppingItem::AVAILABILITY));
        self::assertSame('new', $item->get(GoogleShoppingItem::CONDITION));
        self::assertSame(Availability::IN_STOCK, $item->getAvailability());
        self::assertSame(Condition::NEW, $item->getCondition());
    }

    /**
     * @test
     */
    public function it_returns_null_for_unset_or_unrecognised_fields(): void
    {
        $item = $this->createItem();

        self::assertNull($item->getId());
        self::assertNull($item->getAvailability());

        $item->set(GoogleShoppingItem::AVAILABILITY, 'nonsense');
        self::assertNull($item->getAvailability());
    }
}
