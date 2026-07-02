<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
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
 * End-to-end publish gate (§6.6): a feed whose candidate trips a `block` guardrail must complete
 * without overwriting the live canonical file. The candidate is recorded as `blocked`, its staging
 * file is retained for inspection, and the previously served file is kept live.
 */
final class PublishGateFlowTest extends FunctionalTestCase
{
    private const CONTEXT = 'web_en_us_usd';

    private const LIVE_CONTENT = '<rss>OLD-LIVE-FEED</rss>';

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
    public function it_blocks_promotion_and_keeps_the_live_file_when_a_guardrail_trips(): void
    {
        // the single generated item cannot satisfy a min_items guardrail of 100
        $feedId = (int) $this->persistFixtures([
            'guardrails' => [
                ['type' => 'min_items', 'params' => ['min' => 100], 'severity' => 'block'],
            ],
        ])->getId();

        // seed a previously published canonical file so we can prove it is kept live
        $canonical = self::getContainer()->get('setono_sylius_feed.storage.feed');
        self::assertInstanceOf(FilesystemOperator::class, $canonical);
        $canonical->write('google/' . self::CONTEXT . '.xml', self::LIVE_CONTENT);

        $commandBus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $commandBus);
        $commandBus->dispatch(new ProcessFeed($feedId));

        $this->entityManager()->clear();

        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(RepositoryInterface::class, $repository);
        $feed = $repository->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState());

        // the candidate was gated: recorded as blocked with a reason
        $resultRepository = self::getContainer()->get('setono_sylius_feed.repository.feed_context_result');
        self::assertInstanceOf(FeedContextResultRepositoryInterface::class, $resultRepository);
        $result = $resultRepository->findLatestForContext($feed, self::CONTEXT);
        self::assertNotNull($result);
        self::assertTrue($result->isBlocked());
        self::assertNotNull($result->getPublishCheck());

        // the live canonical file was NOT overwritten
        self::assertSame(self::LIVE_CONTENT, $canonical->read('google/' . self::CONTEXT . '.xml'));

        // the blocked candidate is retained in staging for inspection
        $temporary = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $temporary);
        self::assertTrue($temporary->fileExists('google/' . self::CONTEXT . '.xml'));
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    /**
     * @param array<string, mixed> $publishConfig
     */
    private function persistFixtures(array $publishConfig): FeedInterface
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
        $feed->setPublishConfig($publishConfig);

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
