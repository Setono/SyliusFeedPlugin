<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistry;
use Setono\SyliusFeedPlugin\Format\FormatRegistry;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceRegistry;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistry;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistry;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistry;

/**
 * Proves the bundle wires every extension-point registry from its tag (the _instanceof
 * autoconfiguration + tagged_iterator injection), registered under its FQCN service id.
 */
final class RegistryServicesTest extends FunctionalTestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public function registryProvider(): iterable
    {
        yield 'feed type' => [FeedTypeRegistry::class];
        yield 'mapping preset' => [MappingPresetRegistry::class];
        yield 'value resolver' => [ValueResolverRegistry::class];
        yield 'lookup source' => [LookupSourceRegistry::class];
        yield 'transformation' => [TransformationRegistry::class];
        yield 'operator' => [OperatorRegistry::class];
        yield 'writer' => [FeedWriterRegistry::class];
        yield 'format' => [FormatRegistry::class];
    }

    /**
     * @test
     *
     * @dataProvider registryProvider
     *
     * @param class-string $registryClass
     */
    public function it_registers_each_registry_under_its_fqcn(string $registryClass): void
    {
        $registry = self::getContainer()->get($registryClass);

        self::assertInstanceOf($registryClass, $registry);
        self::assertInstanceOf(\Countable::class, $registry);
        self::assertInstanceOf(\IteratorAggregate::class, $registry);
    }
}
