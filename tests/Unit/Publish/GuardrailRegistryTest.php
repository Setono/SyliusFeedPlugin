<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Publish\GuardrailRegistry;
use Setono\SyliusFeedPlugin\Publish\MinItemsGuardrail;
use Setono\SyliusFeedPlugin\Publish\NonEmptyGuardrail;

final class GuardrailRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function it_keys_guardrails_by_their_type(): void
    {
        $minItems = new MinItemsGuardrail();
        $nonEmpty = new NonEmptyGuardrail();

        $registry = new GuardrailRegistry([$minItems, $nonEmpty]);

        self::assertTrue($registry->has('min_items'));
        self::assertTrue($registry->has('non_empty'));
        self::assertFalse($registry->has('does_not_exist'));
        self::assertSame($minItems, $registry->get('min_items'));
        self::assertSame($nonEmpty, $registry->get('non_empty'));
        self::assertCount(2, $registry);
    }

    /**
     * @test
     */
    public function it_rejects_duplicate_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GuardrailRegistry([new NonEmptyGuardrail(), new NonEmptyGuardrail()]);
    }
}
