<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Model\FeedTranslation;

/**
 * Proves the Doctrine mappings load with the expected table names and associations. Reads
 * mapping metadata only, so it needs no database connection.
 */
final class DoctrineMappingTest extends FunctionalTestCase
{
    private function entityManager(): EntityManagerInterface
    {
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        $manager = $registry->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public function entityProvider(): iterable
    {
        yield 'feed' => [Feed::class, 'setono_sylius_feed__feed'];
        yield 'feed source' => [FeedSource::class, 'setono_sylius_feed__feed_source'];
        yield 'feed field' => [FeedField::class, 'setono_sylius_feed__feed_field'];
        yield 'feed filter' => [FeedFilter::class, 'setono_sylius_feed__feed_filter'];
        yield 'feed translation' => [FeedTranslation::class, 'setono_sylius_feed__feed_translation'];
    }

    /**
     * @test
     *
     * @dataProvider entityProvider
     *
     * @param class-string $class
     */
    public function it_maps_each_entity_to_the_prefixed_table(string $class, string $expectedTable): void
    {
        self::assertSame($expectedTable, $this->entityManager()->getClassMetadata($class)->getTableName());
    }

    /**
     * @test
     */
    public function it_maps_the_feed_associations(): void
    {
        $metadata = $this->entityManager()->getClassMetadata(Feed::class);

        self::assertTrue($metadata->hasAssociation('channels'));
        self::assertTrue($metadata->hasAssociation('sources'));
        self::assertTrue($metadata->hasAssociation('translations'));
    }
}
