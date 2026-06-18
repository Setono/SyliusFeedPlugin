<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Transformation\TransformationInterface;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;

/**
 * @covers \Setono\SyliusFeedPlugin\Transformation\TransformationRegistry
 */
final class TransformationRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function transformation(string $type): TransformationInterface
    {
        $transformation = $this->prophesize(TransformationInterface::class);
        $transformation->getType()->willReturn($type);

        return $transformation->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_transformations_keyed_by_type(): void
    {
        $truncate = $this->transformation('truncate');

        $registry = new TransformationRegistry([$truncate]);

        self::assertSame($truncate, $registry->get('truncate'));
        self::assertTrue($registry->has('truncate'));
        self::assertFalse($registry->has('strip_tags'));
        self::assertSame(['truncate' => $truncate], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_transformations_share_a_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TransformationRegistry([$this->transformation('truncate'), $this->transformation('truncate')]);
    }
}
