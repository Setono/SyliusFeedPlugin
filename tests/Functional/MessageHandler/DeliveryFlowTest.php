<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\DeliveryTarget;
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * End-to-end delivery (§12): a completed feed with a matching `local` delivery target has its
 * promoted, published context file pushed to the target filesystem, and the outcome recorded on the
 * FeedContextResult. Proves the finalize hook, the DI wiring and the Doctrine mapping together.
 */
final class DeliveryFlowTest extends FunctionalTestCase
{
    private string $deliveryDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deliveryDir = sys_get_temp_dir() . '/ssfp-delivery-flow-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'] as $id) {
            $filesystem = self::getContainer()->get($id);
            if ($filesystem instanceof FilesystemOperator) {
                $filesystem->deleteDirectory('google');
            }
        }

        (new Filesystem())->remove($this->deliveryDir);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_delivers_a_published_context_to_a_matching_local_target(): void
    {
        $feedId = (int) $this->persistFixtures()->getId();

        $commandBus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $commandBus);
        $commandBus->dispatch(new ProcessFeed($feedId));

        $this->entityManager()->clear();
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(\Sylius\Component\Resource\Repository\RepositoryInterface::class, $repository);
        $feed = $repository->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState());

        // The generated file was pushed to the local delivery target.
        self::assertFileExists($this->deliveryDir . '/delivered/web_en_us_usd.xml');
        self::assertStringContainsString(
            '<g:id>VARIANT-1</g:id>',
            (string) file_get_contents($this->deliveryDir . '/delivered/web_en_us_usd.xml'),
        );

        // ...and the outcome was recorded on the context result.
        $resultRepository = self::getContainer()->get('setono_sylius_feed.repository.feed_context_result');
        self::assertInstanceOf(FeedContextResultRepositoryInterface::class, $resultRepository);
        $result = $resultRepository->findLatestForContext($feed, 'web_en_us_usd');
        self::assertNotNull($result);

        $deliveries = $result->getDeliveries();
        self::assertCount(1, $deliveries);
        self::assertSame('delivered', $deliveries[0]['status']);
        self::assertSame('delivered/web_en_us_usd.xml', $deliveries[0]['path']);
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

        $target = new DeliveryTarget();
        $target->setTransport('local');
        $target->setTransportConfig(['path' => $this->deliveryDir]);
        $target->setPathTemplate('delivered/{contextKey}.{ext}');
        $feed->addDeliveryTarget($target);

        $manager = $this->entityManager();
        foreach ([$currency, $locale, $channel, $product, $variant, $feed] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();

        return $feed;
    }
}
