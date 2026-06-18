<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\Model\FeedField
 */
final class FeedFieldTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_defaults_to_a_field_source_with_no_transformations(): void
    {
        $field = new FeedField();

        self::assertNull($field->getId());
        self::assertSame(SourceType::FIELD->value, $field->getSourceType());
        self::assertSame([], $field->getTransformations());
        self::assertNull($field->getCondition());
        self::assertFalse($field->getRequiresInput());
    }

    /**
     * @test
     */
    public function it_holds_its_output_mapping(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class)->reveal();

        $field = new FeedField();
        $field->setSource($source);
        $field->setOutputField('g:title');
        $field->setPosition(3);
        $field->setSourceType(SourceType::EXPRESSION->value);
        $field->setSourceValue('entity.getName()');
        $field->setTransformations([['type' => 'truncate', 'params' => ['max' => 150]]]);
        $field->setCondition(['field' => 'on_sale', 'operator' => 'true']);
        $field->setRequiresInput(true);

        self::assertSame($source, $field->getSource());
        self::assertSame('g:title', $field->getOutputField());
        self::assertSame(3, $field->getPosition());
        self::assertSame(SourceType::EXPRESSION->value, $field->getSourceType());
        self::assertSame('entity.getName()', $field->getSourceValue());
        self::assertSame([['type' => 'truncate', 'params' => ['max' => 150]]], $field->getTransformations());
        self::assertSame(['field' => 'on_sale', 'operator' => 'true'], $field->getCondition());
        self::assertTrue($field->getRequiresInput());
    }
}
