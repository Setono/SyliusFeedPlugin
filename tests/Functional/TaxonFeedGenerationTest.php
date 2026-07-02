<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Sylius\Component\Core\Model\Taxon;

/**
 * End-to-end acceptance test for the `taxon` feed type (§8.4): persists a taxon with a localized
 * name/slug and a CSV feed whose source maps taxon-level fields, runs the real generator
 * (including the Doctrine taxon data source) against the database, and asserts the stored CSV
 * carries the taxon row — a second proof point (alongside the order export) that the engine is
 * resource-agnostic (§6, §8.4).
 *
 * Unlike the product and order feed types, the taxon feed type declares no channel scope
 * dimension, so no channel/currency needs to be persisted here — the minimal fixture a taxon row
 * needs is the taxon itself.
 *
 * Runs inside a transaction that is rolled back (dama/doctrine-test-bundle), so the database stays
 * clean; generated files are removed in tearDown.
 */
final class TaxonFeedGenerationTest extends FunctionalTestCase
{
    /** @var list<string> */
    private array $generatedPaths = [];

    protected function tearDown(): void
    {
        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        if ($filesystem instanceof FilesystemOperator) {
            foreach ($this->generatedPaths as $path) {
                if ($filesystem->fileExists($path)) {
                    $filesystem->delete($path);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_generates_a_taxon_csv_feed_from_the_database(): void
    {
        $this->persistFixtures();

        $feed = new Feed();
        $feed->setCode('taxons');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Taxon feed');
        $feed->setSlug('taxon-feed');
        $feed->addSource($this->source('taxon', 0, ['code', 'name']));

        $this->persist($feed);

        $result = $this->generate($feed, new FeedContext(null, 'en_US'));

        self::assertSame('taxons/en_us.csv', $result->path);
        self::assertSame(1, $result->itemCount);

        $reader = $this->read($result->path);

        self::assertSame(['code', 'name'], $reader->getHeader());

        $records = $this->csvRecords($reader);
        self::assertCount(1, $records);
        $row = $records[0];
        self::assertSame('CATEGORY-1', $row['code']);
        self::assertSame('Shoes', $row['name']);
    }

    /**
     * @param list<string> $fields
     */
    private function source(string $feedType, int $position, array $fields): FeedSourceInterface
    {
        $source = new FeedSource();
        $source->setFeedType($feedType);
        $source->setPosition($position);

        foreach ($fields as $index => $name) {
            $source->addField($this->field($name, $index));
        }

        return $source;
    }

    private function field(string $name, int $position): FeedFieldInterface
    {
        $field = new FeedField();
        $field->setOutputField($name);
        $field->setSourceType('field');
        $field->setSourceValue($name);
        $field->setPosition($position);

        return $field;
    }

    private function generate(Feed $feed, FeedContext $context): GenerationResult
    {
        $generator = self::getContainer()->get(FeedGenerator::class);
        self::assertInstanceOf(FeedGenerator::class, $generator);

        $result = $generator->generate($feed, $context);
        $this->generatedPaths[] = $result->path;

        return $result;
    }

    private function read(string $path): Reader
    {
        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $filesystem);

        $reader = Reader::createFromString($filesystem->read($path));
        $reader->setHeaderOffset(0);

        return $reader;
    }

    /**
     * Normalises league/csv records to arrays through a mixed boundary — older league/csv versions
     * type `getRecords()` loosely (mixed), newer ones precisely, so this stays valid on both.
     *
     * @return list<array<int|string, mixed>>
     */
    private function csvRecords(Reader $reader): array
    {
        return array_values(array_filter([...$reader->getRecords()], is_array(...)));
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function persist(object $entity): void
    {
        $manager = $this->entityManager();
        $manager->persist($entity);
        $manager->flush();
    }

    private function persistFixtures(): void
    {
        $taxon = new Taxon();
        $taxon->setCode('CATEGORY-1');
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');
        $taxon->setName('Shoes');
        $taxon->setSlug('shoes');
        $taxon->setEnabled(true);

        $this->persist($taxon);
    }
}
