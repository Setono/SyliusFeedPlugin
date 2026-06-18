<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Mapping;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * @covers \Setono\SyliusFeedPlugin\Mapping\TransformationConfig
 */
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
}
