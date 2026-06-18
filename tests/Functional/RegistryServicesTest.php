<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistry;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceRegistry;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistry;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistry;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistry;

/**
 * Proves the bundle wires every extension-point registry from its tag (the _instanceof
 * autoconfiguration + tagged_iterator injection in the DI extension).
 */
final class RegistryServicesTest extends FunctionalTestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public function registryProvider(): iterable
    {
        yield 'feed type' => ['setono_sylius_feed.registry.feed_type', FeedTypeRegistry::class];
        yield 'mapping preset' => ['setono_sylius_feed.registry.mapping_preset', MappingPresetRegistry::class];
        yield 'value resolver' => ['setono_sylius_feed.registry.value_resolver', ValueResolverRegistry::class];
        yield 'lookup source' => ['setono_sylius_feed.registry.lookup_source', LookupSourceRegistry::class];
        yield 'transformation' => ['setono_sylius_feed.registry.transformation', TransformationRegistry::class];
        yield 'operator' => ['setono_sylius_feed.registry.operator', OperatorRegistry::class];
        yield 'writer' => ['setono_sylius_feed.registry.writer', FeedWriterRegistry::class];
    }

    /**
     * @test
     *
     * @dataProvider registryProvider
     *
     * @param class-string $expectedClass
     */
    public function it_registers_each_registry_service(string $serviceId, string $expectedClass): void
    {
        $registry = self::getContainer()->get($serviceId);

        self::assertInstanceOf($expectedClass, $registry);
        // No extension-point services are tagged yet in M0, so each registry is empty but wired.
        self::assertInstanceOf(\Countable::class, $registry);
        self::assertCount(0, $registry);
    }
}
