<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * End-to-end M2 lifecycle: dispatching {@see ProcessFeed} (handled synchronously here) runs the
 * full workflow — process → generate each context to temporary storage → complete → atomic swap to
 * canonical storage — leaving the feed completed and its file publicly readable.
 */
final class ProcessFeedFlowTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        foreach (['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'] as $id) {
            $filesystem = self::getContainer()->get($id);
            if ($filesystem instanceof FilesystemOperator) {
                $filesystem->deleteDirectory('google');
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_processes_a_feed_end_to_end(): void
    {
        $feedId = (int) $this->persistFixtures()->getId();

        $commandBus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $commandBus);
        $commandBus->dispatch(new ProcessFeed($feedId));

        $this->entityManager()->clear();
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(RepositoryInterface::class, $repository);
        $feed = $repository->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState());
        self::assertSame(1, $feed->getContextCount());
        self::assertSame(1, $feed->getCompletedContextCount());
        self::assertNotNull($feed->getLastGeneratedAt());

        // the file was swapped from temporary to canonical storage
        $canonical = self::getContainer()->get('setono_sylius_feed.storage.feed');
        self::assertInstanceOf(FilesystemOperator::class, $canonical);
        self::assertTrue($canonical->fileExists('google/web_en_us_usd.xml'));
        self::assertStringContainsString('<g:id>VARIANT-1</g:id>', $canonical->read('google/web_en_us_usd.xml'));

        $temporary = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $temporary);
        self::assertFalse($temporary->fileExists('google/web_en_us_usd.xml'));
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function persistFixtures(): FeedInterface
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
        foreach ([$currency, $locale, $channel, $product, $variant, $feed] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();

        return $feed;
    }
}
