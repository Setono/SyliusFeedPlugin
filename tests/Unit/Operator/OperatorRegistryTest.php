<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Operator\OperatorInterface;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;

final class OperatorRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function operator(string $name): OperatorInterface
    {
        $operator = $this->prophesize(OperatorInterface::class);
        $operator->getName()->willReturn($name);

        return $operator->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_operators_keyed_by_name(): void
    {
        $contains = $this->operator('contains');

        $registry = new OperatorRegistry([$contains]);

        self::assertSame($contains, $registry->get('contains'));
        self::assertTrue($registry->has('contains'));
        self::assertFalse($registry->has('between'));
        self::assertSame(['contains' => $contains], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_operators_share_a_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OperatorRegistry([$this->operator('in'), $this->operator('in')]);
    }
}
