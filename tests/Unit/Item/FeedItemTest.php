<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Item;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * @covers \Setono\SyliusFeedPlugin\Item\FeedItem
 */
final class FeedItemTest extends TestCase
{
    private function createItem(): FeedItem
    {
        return new FeedItem(new \stdClass(), new FeedContext());
    }

    /**
     * @test
     */
    public function it_exposes_the_entity_and_context(): void
    {
        $entity = new \stdClass();
        $context = new FeedContext();

        $item = new FeedItem($entity, $context);

        self::assertSame($entity, $item->getEntity());
        self::assertSame($context, $item->getContext());
    }

    /**
     * @test
     */
    public function it_reads_and_writes_fields_through_the_bag(): void
    {
        $item = $this->createItem();

        self::assertFalse($item->has('g:title'));
        self::assertNull($item->get('g:title'));

        $item->set('g:title', 'Acme Shoe');

        self::assertTrue($item->has('g:title'));
        self::assertSame('Acme Shoe', $item->get('g:title'));

        $item->remove('g:title');

        self::assertFalse($item->has('g:title'));
    }

    /**
     * @test
     */
    public function it_keeps_a_null_value_distinct_from_an_absent_field(): void
    {
        $item = $this->createItem();
        $item->set('g:sale_price', null);

        self::assertTrue($item->has('g:sale_price'));
        self::assertNull($item->get('g:sale_price'));
    }

    /**
     * @test
     */
    public function it_preserves_insertion_order_in_the_bag(): void
    {
        $item = $this->createItem();
        $item->set('g:id', '1');
        $item->set('g:title', 'Shoe');
        $item->set('g:price', '10.00 USD');

        self::assertSame(['g:id', 'g:title', 'g:price'], array_keys($item->all()));
    }

    /**
     * @test
     */
    public function it_can_be_vetoed(): void
    {
        $item = $this->createItem();

        self::assertFalse($item->isSkipped());

        $item->skip();

        self::assertTrue($item->isSkipped());
    }

    /**
     * @test
     */
    public function it_supports_array_access(): void
    {
        $item = $this->createItem();

        self::assertFalse(isset($item['g:id']));

        $item['g:id'] = 'SKU-1';

        self::assertTrue(isset($item['g:id']));
        self::assertSame('SKU-1', $item['g:id']);

        unset($item['g:id']);

        self::assertFalse(isset($item['g:id']));
    }

    /**
     * @test
     */
    public function it_is_iterable_and_countable(): void
    {
        $item = $this->createItem();
        $item->set('g:id', '1');
        $item->set('g:title', 'Shoe');

        self::assertCount(2, $item);
        self::assertSame(['g:id' => '1', 'g:title' => 'Shoe'], iterator_to_array($item));
    }
}
