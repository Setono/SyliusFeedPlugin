<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Mapping;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

final class FieldDefinitionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_describes_an_available_source_field(): void
    {
        $resolver = $this->prophesize(ValueResolverInterface::class);

        $definition = new FieldDefinition('main_image', 'Main image', FieldType::IMAGE, $resolver->reveal(), true);

        self::assertSame('main_image', $definition->getName());
        self::assertSame('Main image', $definition->getLabel());
        self::assertSame(FieldType::IMAGE, $definition->getType());
        self::assertSame($resolver->reveal(), $definition->getResolver());
        self::assertTrue($definition->isMultiple());
    }

    /**
     * @test
     */
    public function it_defaults_to_a_single_valued_field(): void
    {
        $resolver = $this->prophesize(ValueResolverInterface::class);

        $definition = new FieldDefinition('id', 'ID', FieldType::STRING, $resolver->reveal());

        self::assertFalse($definition->isMultiple());
    }
}
