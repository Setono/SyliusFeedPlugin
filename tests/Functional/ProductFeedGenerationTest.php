<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;

/**
 * End-to-end acceptance test for the `product` feed type (§8.7): persists a channel/product/variant
 * and a CSV feed whose sources map product-level fields, runs the real generator (including the
 * Doctrine data source) against the database, and asserts the stored CSV carries product-level rows.
 * Also covers multi-source generation: one feed with both a `product` and a `product_variant` source
 * emits both product-level and variant-level rows.
 *
 * Each test runs inside a transaction that is rolled back (dama/doctrine-test-bundle), so the
 * database stays clean; generated files are removed in tearDown.
 */
final class ProductFeedGenerationTest extends FunctionalTestCase
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
    public function it_generates_a_product_level_csv_feed_from_the_database(): void
    {
        $channel = $this->persistFixtures();

        $feed = new Feed();
        $feed->setCode('products');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Product feed');
        $feed->setSlug('product-feed');
        $feed->addChannel($channel);
        $feed->addSource($this->source('product', 0, ['id', 'title', 'from_price']));

        $this->persist($feed);

        $result = $this->generate($feed, new FeedContext($channel, 'en_US', 'USD'));

        self::assertSame('products/web_en_us_usd.csv', $result->path);
        self::assertSame(1, $result->itemCount);

        $reader = $this->read($result->path);

        self::assertSame(['id', 'title', 'from_price'], $reader->getHeader());

        $records = $this->csvRecords($reader);
        self::assertCount(1, $records);
        $row = $records[0];
        self::assertSame('PROD-1', $row['id']);
        self::assertSame('Acme Shoe', $row['title']);
        self::assertSame('999', $row['from_price']);
    }

    /**
     * @test
     */
    public function it_generates_a_multi_source_feed_with_product_and_variant_rows(): void
    {
        $channel = $this->persistFixtures();

        $feed = new Feed();
        $feed->setCode('catalog');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Catalog feed');
        $feed->setSlug('catalog-feed');
        $feed->addChannel($channel);
        $feed->addSource($this->source('product', 0, ['id', 'title', 'from_price']));
        $feed->addSource($this->source('product_variant', 1, ['id', 'title', 'channel_price']));

        $this->persist($feed);

        $result = $this->generate($feed, new FeedContext($channel, 'en_US', 'USD'));

        self::assertSame('catalog/web_en_us_usd.csv', $result->path);
        self::assertSame(2, $result->itemCount, 'one product row + one variant row');

        $reader = $this->read($result->path);

        $header = $reader->getHeader();
        self::assertSame(['id', 'title', 'from_price', 'channel_price'], $header);

        $byId = [];
        foreach ($this->csvRecords($reader) as $record) {
            $id = $record['id'];
            self::assertIsString($id);
            $byId[$id] = $record;
        }

        // product-level row
        self::assertArrayHasKey('PROD-1', $byId);
        self::assertSame('Acme Shoe', $byId['PROD-1']['title']);
        self::assertSame('999', $byId['PROD-1']['from_price']);
        self::assertSame('', $byId['PROD-1']['channel_price'], 'from_price is a product-only field, channel_price is variant-only');

        // variant-level row
        self::assertArrayHasKey('VARIANT-1', $byId);
        self::assertSame('Acme Shoe', $byId['VARIANT-1']['title']);
        self::assertSame('999', $byId['VARIANT-1']['channel_price']);
        self::assertSame('', $byId['VARIANT-1']['from_price']);
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

    private function generate(Feed $feed, FeedContext $context): \Setono\SyliusFeedPlugin\Generator\GenerationResult
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
        return array_values(array_filter(iterator_to_array($reader->getRecords()), is_array(...)));
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

    private function persistFixtures(): Channel
    {
        $currency = new Currency();
        $currency->setCode('USD');

        $locale = new Locale();
        $locale->setCode('en_US');

        $channel = new Channel();
        $channel->setCode('web');
        $channel->setName('Web');
        $channel->setHostname('localhost');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addCurrency($currency);
        $channel->addLocale($locale);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setEnabled(true);

        $product = new Product();
        $product->setCode('PROD-1');
        $product->setEnabled(true);
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        $product->setName('Acme Shoe');
        $product->setSlug('acme-shoe');
        $product->setDescription('A very nice shoe');
        $product->addChannel($channel);

        $variant = new ProductVariant();
        $variant->setCode('VARIANT-1');
        $variant->setEnabled(true);

        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('web');
        $channelPricing->setPrice(999);
        $variant->addChannelPricing($channelPricing);

        $product->addVariant($variant);

        $manager = $this->entityManager();
        foreach ([$currency, $locale, $channel, $product, $variant] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();

        return $channel;
    }
}
