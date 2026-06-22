<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;

/**
 * End-to-end acceptance test: persists a channel/product/variant + a Google Shopping feed, runs the
 * real generator (including the Doctrine data source) against the database, and asserts the stored
 * file is a valid Google RSS document carrying the variant's data.
 *
 * Each test runs inside a transaction that is rolled back (dama/doctrine-test-bundle), so the
 * database stays clean; the generated file is removed in tearDown.
 */
final class FeedGenerationTest extends FunctionalTestCase
{
    private ?string $generatedPath = null;

    protected function tearDown(): void
    {
        if (null !== $this->generatedPath) {
            $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
            if ($filesystem instanceof FilesystemOperator && $filesystem->fileExists($this->generatedPath)) {
                $filesystem->delete($this->generatedPath);
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_generates_a_valid_google_rss_feed_from_the_database(): void
    {
        $channel = $this->persistFixtures();

        $generator = self::getContainer()->get(FeedGenerator::class);
        self::assertInstanceOf(FeedGenerator::class, $generator);

        $feed = $this->feed($channel);

        $result = $generator->generate($feed, new FeedContext($channel, 'en_US', 'USD'));
        $this->generatedPath = $result->path;

        self::assertSame('google/web_en_us_usd.xml', $result->path);
        self::assertSame(1, $result->itemCount);

        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $filesystem);
        $xml = $filesystem->read($result->path);

        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml), 'The generated feed must be well-formed XML');

        self::assertStringContainsString('xmlns:g="http://base.google.com/ns/1.0"', $xml);
        self::assertStringContainsString('<g:id>VARIANT-1</g:id>', $xml);
        self::assertStringContainsString('<g:title>Acme Shoe</g:title>', $xml);
        self::assertStringContainsString('<g:availability>in_stock</g:availability>', $xml);
        self::assertStringContainsString('<g:price>9.99 USD</g:price>', $xml);
        self::assertStringContainsString('<g:condition>new</g:condition>', $xml);
        self::assertStringContainsString('acme-shoe', $xml);
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
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

        $image = new ProductImage();
        $image->setPath('acme.jpg');
        $product->addImage($image);

        $variant = new ProductVariant();
        $variant->setCode('VARIANT-1');

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

    private function feed(Channel $channel): Feed
    {
        $feed = new Feed();
        $feed->setCode('google');
        $feed->setFormat('google_rss');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Google feed');
        $feed->setSlug('google-feed');
        $feed->addChannel($channel);

        $source = new FeedSource();
        $source->setFeedType('product_variant');
        $source->setPosition(0);
        $feed->addSource($source);

        $manager = $this->entityManager();
        $manager->persist($feed);
        $manager->flush();

        return $feed;
    }
}
