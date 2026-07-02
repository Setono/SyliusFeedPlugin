<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * M7 fan-out acceptance through the fully wired lifecycle (§6.3): dispatching {@see ProcessFeed} for a
 * feed configured to fan out runs the whole chain — process → GenerateFeedContext (fan-out) →
 * GenerateFeedChunk × N → FinalizeFeedContext → complete — synchronously here, and the promoted
 * canonical file is byte-identical to the inline generation of the same feed.
 */
final class FanOutGenerationTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        foreach (['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'] as $id) {
            $filesystem = self::getContainer()->get($id);
            if ($filesystem instanceof FilesystemOperator) {
                $filesystem->deleteDirectory('catalog');
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_fans_out_and_produces_a_byte_identical_file(): void
    {
        $channel = $this->persistFixtures();
        $feed = $this->persistFanOutFeed($channel);
        $feedId = (int) $feed->getId();

        $bus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        $bus->dispatch(new ProcessFeed($feedId));

        $feed = $this->reload($feedId);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState());
        self::assertSame(1, $feed->getContextCount());
        self::assertSame(1, $feed->getCompletedContextCount());

        $canonical = self::getContainer()->get('setono_sylius_feed.storage.feed');
        self::assertInstanceOf(FilesystemOperator::class, $canonical);
        self::assertTrue($canonical->fileExists('catalog/web_en_us_usd.csv'));
        $fanOutBytes = $canonical->read('catalog/web_en_us_usd.csv');

        // Every variant appears exactly once, in id order.
        $reader = Reader::createFromString($fanOutBytes);
        $reader->setHeaderOffset(0);
        $ids = array_map(static fn (array $row): mixed => $row['id'] ?? null, array_values([...$reader->getRecords()]));
        self::assertSame(['VARIANT-1', 'VARIANT-2', 'VARIANT-3'], $ids);

        // Byte-identical to an inline generation of the same feed.
        $generator = self::getContainer()->get(FeedGenerator::class);
        self::assertInstanceOf(FeedGenerator::class, $generator);
        $inline = $generator->generate($feed, new FeedContext($channel, 'en_US', 'USD'));
        $tmp = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $tmp);
        $inlineBytes = $tmp->read($inline->path);
        $tmp->delete($inline->path);

        self::assertSame($inlineBytes, $fanOutBytes, 'the wired fan-out output must be byte-identical to the inline output');
    }

    private function reload(int $feedId): FeedInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $manager->clear();

        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(FeedRepositoryInterface::class, $repository);
        $feed = $repository->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);

        return $feed;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function persistFanOutFeed(Channel $channel): FeedInterface
    {
        $feed = new Feed();
        $feed->setCode('catalog');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setState(FeedGraph::STATE_READY);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Catalog feed');
        $feed->setSlug('catalog-feed');
        $feed->setFormatConfig(['chunk' => ['size' => 1]]);
        $feed->addChannel($channel);

        $source = new FeedSource();
        $source->setFeedType('product_variant');
        $source->setPosition(0);
        foreach (['id', 'title', 'channel_price'] as $position => $name) {
            $field = new FeedField();
            $field->setOutputField($name);
            $field->setSourceType('field');
            $field->setSourceValue($name);
            $field->setPosition($position);
            $source->addField($field);
        }
        $feed->addSource($source);

        $manager = $this->entityManager();
        $manager->persist($feed);
        $manager->flush();

        return $feed;
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

        $manager = $this->entityManager();
        foreach ([$currency, $locale, $channel] as $entity) {
            $manager->persist($entity);
        }

        for ($i = 1; $i <= 3; ++$i) {
            $product = new Product();
            $product->setCode('PROD-' . $i);
            $product->setEnabled(true);
            $product->setCurrentLocale('en_US');
            $product->setFallbackLocale('en_US');
            $product->setName('Product ' . $i);
            $product->setSlug('product-' . $i);
            $product->addChannel($channel);

            $variant = new ProductVariant();
            $variant->setCode('VARIANT-' . $i);
            $variant->setEnabled(true);

            $channelPricing = new ChannelPricing();
            $channelPricing->setChannelCode('web');
            $channelPricing->setPrice(100 * $i);
            $variant->addChannelPricing($channelPricing);

            $product->addVariant($variant);

            $manager->persist($product);
            $manager->persist($variant);
        }

        $manager->flush();

        return $channel;
    }
}
