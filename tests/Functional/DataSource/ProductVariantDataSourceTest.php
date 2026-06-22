<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\DataSource;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\ProductVariantDataSource;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;

final class ProductVariantDataSourceTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_exposes_the_variant_class_and_counts_enabled_channel_assigned_variants(): void
    {
        $channel = $this->persistFixtures();

        $dataSource = self::getContainer()->get(ProductVariantDataSource::class);
        self::assertInstanceOf(ProductVariantDataSource::class, $dataSource);

        self::assertTrue(is_a($dataSource->getResourceClass(), ProductVariantInterface::class, true));
        self::assertSame(1, $dataSource->count(new FeedContext($channel, 'en_US', 'USD'), new FilterSet([])));
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
        $product->addVariant($variant);

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        foreach ([$currency, $locale, $channel, $product, $variant] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();

        return $channel;
    }
}
