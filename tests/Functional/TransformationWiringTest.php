<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistryInterface;
use Setono\SyliusFeedPlugin\Transformation\Truncate;

/**
 * Proves the built-in transformations are auto-tagged and collected into the registry through the
 * DI prototype + `_instanceof` autoconfiguration.
 */
final class TransformationWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_collects_the_built_in_transformations(): void
    {
        $registry = self::getContainer()->get(TransformationRegistry::class);

        self::assertInstanceOf(TransformationRegistryInterface::class, $registry);
        self::assertInstanceOf(Truncate::class, $registry->get('truncate'));
        self::assertInstanceOf(StripTags::class, $registry->get('strip_tags'));
        self::assertInstanceOf(MoneyFormat::class, $registry->get('money_format'));
    }
}
