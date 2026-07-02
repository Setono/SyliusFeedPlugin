<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Mapping;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

final class TransformationConfigTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type_and_params(): void
    {
        $config = new TransformationConfig('truncate', ['max' => 150, 'ellipsis' => '…']);

        self::assertSame('truncate', $config->getType());
        self::assertSame(['max' => 150, 'ellipsis' => '…'], $config->getParams());
    }

    /**
     * @test
     */
    public function it_defaults_to_empty_params(): void
    {
        self::assertSame([], (new TransformationConfig('strip_tags'))->getParams());
    }

    /**
     * @test
     */
    public function it_round_trips_through_an_array(): void
    {
        $data = ['type' => 'truncate', 'params' => ['max' => 5]];

        self::assertSame($data, TransformationConfig::fromArray($data)->toArray());
    }

    /**
     * @test
     */
    public function it_defaults_params_to_an_empty_array_when_hydrated_from_an_array_without_params(): void
    {
        self::assertSame(['type' => 'x', 'params' => []], TransformationConfig::fromArray(['type' => 'x'])->toArray());
    }

    /**
     * @test
     */
    public function it_throws_when_hydrated_from_an_array_missing_a_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransformationConfig::fromArray([]);
    }

    /**
     * @test
     */
    public function it_throws_when_hydrated_from_an_array_with_a_non_string_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransformationConfig::fromArray(['type' => 42]);
    }
}
