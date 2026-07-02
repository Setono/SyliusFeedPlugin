<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Mapping;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Model\FeedField;

final class FieldMappingTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_field_source_mapping(): void
    {
        $mapping = FieldMapping::field('g:title', 'title');

        self::assertSame('g:title', $mapping->getOutputField());
        self::assertSame(SourceType::FIELD, $mapping->getSourceType());
        self::assertSame('title', $mapping->getSourceValue());
        self::assertSame([], $mapping->getTransformations());
        self::assertNull($mapping->getCondition());
        self::assertFalse($mapping->getRequiresInput());
    }

    /**
     * @test
     */
    public function it_creates_literal_expression_and_twig_source_mappings(): void
    {
        self::assertSame(SourceType::LITERAL, FieldMapping::literal('g:condition', 'new')->getSourceType());
        self::assertSame(SourceType::EXPRESSION, FieldMapping::expression('count', 'entity.count()')->getSourceType());
        self::assertSame(SourceType::TWIG, FieldMapping::twig('g:title', '{{ value }}')->getSourceType());
    }

    /**
     * @test
     */
    public function it_appends_transformations_in_order_and_returns_self(): void
    {
        $truncate = new TransformationConfig('truncate', ['max' => 150]);
        $stripTags = new TransformationConfig('strip_tags');

        $mapping = FieldMapping::field('g:description', 'description');
        $result = $mapping->transform($stripTags)->transform($truncate);

        self::assertSame($mapping, $result);
        self::assertSame([$stripTags, $truncate], $mapping->getTransformations());
    }

    /**
     * @test
     */
    public function it_sets_an_emit_condition_via_only_if(): void
    {
        $mapping = FieldMapping::field('g:item_group_id', 'item_group_id')->onlyIf('is_configurable');

        self::assertSame(['field' => 'is_configurable', 'operator' => 'true'], $mapping->getCondition());
    }

    /**
     * @test
     */
    public function it_flags_that_admin_input_is_required(): void
    {
        self::assertTrue(FieldMapping::field('g:brand', 'attribute:brand')->requiresInput()->getRequiresInput());
        self::assertFalse(FieldMapping::field('g:brand', 'attribute:brand')->requiresInput(false)->getRequiresInput());
    }

    /**
     * @test
     */
    public function it_sets_an_emit_condition_via_when(): void
    {
        $mapping = FieldMapping::field('g:id', 'id')->when('a', 'equals', 'b');

        self::assertSame(['field' => 'a', 'operator' => 'equals', 'value' => 'b'], $mapping->getCondition());
    }

    /**
     * @test
     */
    public function it_round_trips_through_a_feed_field(): void
    {
        $mapping = FieldMapping::field('g:brand', 'attribute:brand')
            ->transform(new TransformationConfig('truncate', ['max' => 5]))
            ->when('a', 'equals', 'b')
            ->requiresInput();

        $field = new FeedField();
        $mapping->writeTo($field);

        $hydrated = FieldMapping::fromFeedField($field);

        self::assertSame($mapping->getOutputField(), $hydrated->getOutputField());
        self::assertSame($mapping->getSourceType(), $hydrated->getSourceType());
        self::assertSame($mapping->getSourceValue(), $hydrated->getSourceValue());
        self::assertSame(
            array_map(static fn (TransformationConfig $transformation): array => $transformation->toArray(), $mapping->getTransformations()),
            array_map(static fn (TransformationConfig $transformation): array => $transformation->toArray(), $hydrated->getTransformations()),
        );
        self::assertSame($mapping->getCondition(), $hydrated->getCondition());
        self::assertSame($mapping->getRequiresInput(), $hydrated->getRequiresInput());
    }
}
