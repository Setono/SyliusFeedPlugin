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
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;

/**
 * End-to-end acceptance test for the `order` feed type (§8.2): persists a channel, a customer and
 * a completed order with one item, and a CSV feed whose source maps order-level fields, runs the
 * real generator (including the Doctrine order data source) against the database, and asserts the
 * stored CSV carries the order row — proving the engine that streams a product catalog streams a
 * non-catalog "order export" through the exact same pipeline (§6, §8.2).
 *
 * Runs inside a transaction that is rolled back (dama/doctrine-test-bundle), so the database stays
 * clean; generated files are removed in tearDown.
 */
final class OrderFeedGenerationTest extends FunctionalTestCase
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
    public function it_generates_a_completed_order_csv_export_from_the_database(): void
    {
        $channel = $this->persistFixtures();

        $feed = new Feed();
        $feed->setCode('orders');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Order feed');
        $feed->setSlug('order-feed');
        $feed->addChannel($channel);
        $feed->addSource($this->source('order', 0, ['number', 'total', 'customer_email']));

        $this->persist($feed);

        $result = $this->generate($feed, new FeedContext($channel));

        self::assertSame('orders/web.csv', $result->path);
        self::assertSame(1, $result->itemCount);

        $reader = $this->read($result->path);

        self::assertSame(['number', 'total', 'customer_email'], $reader->getHeader());

        $records = $this->csvRecords($reader);
        self::assertCount(1, $records);
        $row = $records[0];
        self::assertSame('ORDER-1', $row['number']);
        self::assertSame('999', $row['total']);
        self::assertSame('shopper@example.com', $row['customer_email']);
    }

    /**
     * @test
     */
    public function it_excludes_carts_from_the_order_export(): void
    {
        $channel = $this->persistFixtures();

        $cart = new Order();
        $cart->setState(OrderInterface::STATE_CART);
        $cart->setChannel($channel);
        $cart->setCurrencyCode('USD');
        $cart->setLocaleCode('en_US');

        $manager = $this->entityManager();
        $manager->persist($cart);
        $manager->flush();

        $feed = new Feed();
        $feed->setCode('orders');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Order feed');
        $feed->setSlug('order-feed');
        $feed->addChannel($channel);
        $feed->addSource($this->source('order', 0, ['number']));

        $this->persist($feed);

        $result = $this->generate($feed, new FeedContext($channel));

        self::assertSame(1, $result->itemCount, 'only the completed order is included, the cart is excluded');
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
        $product->addChannel($channel);

        $variant = new ProductVariant();
        $variant->setCode('VARIANT-1');
        $variant->setEnabled(true);

        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode('web');
        $channelPricing->setPrice(999);
        $variant->addChannelPricing($channelPricing);

        $product->addVariant($variant);

        $customer = new Customer();
        $customer->setEmail('shopper@example.com');
        $customer->setEmailCanonical('shopper@example.com');

        $orderItem = new OrderItem();
        $orderItem->setVariant($variant);
        $orderItem->setUnitPrice(999);
        new OrderItemUnit($orderItem);

        $order = new Order();
        $order->setNumber('ORDER-1');
        $order->setState(OrderInterface::STATE_NEW);
        $order->setChannel($channel);
        $order->setCustomer($customer);
        $order->setCurrencyCode('USD');
        $order->setLocaleCode('en_US');
        $order->setCheckoutCompletedAt(new \DateTime());
        $order->addItem($orderItem);

        $manager = $this->entityManager();
        foreach ([$currency, $locale, $channel, $product, $variant, $customer, $order] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();

        return $channel;
    }
}
