<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;
use Loevgaard\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * TODO
 * * Use Gaufrette file system abstraction instead of Symfonys
 * * Use a product repository trait to fetch products for the respective channel and locale
 */
final class GenerateFeedsCommand extends ContainerAwareCommand
{
    /**
     * @var FeedRepositoryInterface
     */
    private $feedRepository;

    /**
     * @var ChannelRepositoryInterface
     */
    private $channelRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityManagerInterface
     */
    private $productManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CacheManager
     */
    private $imagineCacheManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository,
        ProductRepositoryInterface $productRepository,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        RouterInterface $router,
        CacheManager $imagineCacheManager,
        TranslatorInterface $translator
    ) {
        parent::__construct();

        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
        $this->productRepository = $productRepository;
        $this->productManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->router = $router;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->translator = $translator;
    }

    protected function configure(): void
    {
        $this
            ->setName('loevgaard:feed:generate')
            ->setDescription('Generates feeds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var FeedInterface[] $feeds */
        $feeds = $this->feedRepository->findAll();

        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        foreach ($feeds as $feed) {
            foreach ($channels as $channel) {
                foreach ($channel->getLocales() as $locale) {
                    $this->generateFeed($feed, $channel, $locale);
                }
            }
        }
    }

    private function generateFeed(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->translator->setLocale($locale->getCode());

        // configure router context
        $context = $this->router->getContext();
        $context->setHost($channel->getHostname());

        $feedDir = $this->getContainer()->getParameter('loevgaard_sylius_feed.dir');

        $tmpFile = $this->getTmpFile();

        $products = $this->getProducts();

        $xmlWriter = new \XMLWriter();
        $xmlWriter->openURI($tmpFile->getPathname());
        $xmlWriter->setIndent(true);
        $xmlWriter->setIndentString('  ');
        $xmlWriter->startDocument('1.0', 'UTF-8');
        $xmlWriter->startElement('products');

        foreach ($products as $product) {
            if (!$product->isEnabled()) {
                continue;
            }

            $link = $this->router->generate('sylius_shop_product_show', ['_locale' => $locale->getCode(), 'slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

            // deduce values based on variants
            $stock = 0;
            $lowestPrice = PHP_INT_MAX;
            $highestPrice = 0;

            foreach ($product->getVariants() as $variant) {
                /** @var ProductVariant $variant */
                $stock += $variant->getOnHand();

                $prices = $variant->getChannelPricings();
                foreach ($prices as $price) {
                    /** @var ChannelPricingInterface $price */
                    if ($price->getPrice() < $lowestPrice) {
                        $lowestPrice = $price->getPrice();
                    }

                    if ($price->getPrice() > $highestPrice) {
                        $highestPrice = $price->getPrice();
                    }
                }
            }

            $data = [
                'id' => $product->getId(),
                'code' => $product->getCode(),
                'link' => $link,
                'name' => $product->getName(),
                'short_description' => $product->getShortDescription(),
                'description' => $product->getDescription(),
                'condition' => 'new',
                'stock' => $stock,
                'in_stock' => $stock > 0,
                'lowest_price' => $lowestPrice,
                'highest_price' => $highestPrice,
                'currency' => $channel->getBaseCurrency()->getCode(),
                'is_master' => true,
                'is_variant' => false,
                'variant_group_id' => $product->getCode(),
                'created_at' => $product->getCreatedAt()->format(DATE_ATOM),
                'updated_at' => $product->getUpdatedAt()->format(DATE_ATOM),
            ];

            $mainTaxonPath = null;

            if ($product->getMainTaxon()) {
                $data['main_taxon'] = $product->getMainTaxon()->getName();
                $mainTaxonPath = $product->getMainTaxon()->getName();

                foreach ($product->getMainTaxon()->getAncestors() as $ancestor) {
                    $mainTaxonPath = $ancestor->getName() . ' > ' . $mainTaxonPath;
                }

                $data['main_taxon_path'] = $this->translator->trans('sylius.ui.home') . ' > ' . $mainTaxonPath;
            }

            $image = null;
            foreach ($product->getImagesByType('main') as $image) {
                $image = $this->imagineCacheManager->getBrowserPath($image->getPath(), 'sylius_shop_product_original');
            }

            if ($image) {
                $data['image'] = $image;
            }

            $gtin = null;

            try {
                $gtin = $propertyAccessor->getValue($product, 'gtin');
            } catch (NoSuchPropertyException $e) {
            }
            if ($gtin) {
                $data['gtin'] = $gtin;
            }

            $brand = null;

            try {
                $brand = $propertyAccessor->getValue($product, 'brand');
            } catch (NoSuchPropertyException $e) {
            }
            if ($brand) {
                $data['brand'] = $brand;
            }

            $this->writeProduct($xmlWriter, $data);

            foreach ($product->getVariants() as $variant) {
                /** @var ProductVariant $variant */
                $data = [
                    'id' => $variant->getId(),
                    'code' => $variant->getCode(),
                    'link' => $link,
                    'name' => $variant->getName(),
                    'short_description' => $product->getShortDescription(),
                    'description' => $product->getDescription(),
                    'condition' => 'new',
                    'stock' => $stock,
                    'in_stock' => $stock > 0,
                    'currency' => $channel->getBaseCurrency()->getCode(),
                    'is_master' => false,
                    'is_variant' => true,
                    'variant_group_id' => $product->getCode(),
                    'created_at' => $variant->getCreatedAt()->format(DATE_ATOM),
                    'updated_at' => $variant->getUpdatedAt()->format(DATE_ATOM),
                ];

                if ($product->getMainTaxon()) {
                    $data['main_taxon'] = $product->getMainTaxon()->getName();
                }

                if ($mainTaxonPath) {
                    $data['main_taxon_path'] = $mainTaxonPath;
                }

                if ($image) {
                    $data['image'] = $image;
                }

                try {
                    $gtin = $propertyAccessor->getValue($variant, 'gtin');
                    if ($gtin) {
                        $data['gtin'] = $gtin;
                    }
                } catch (NoSuchPropertyException $e) {
                }

                if ($brand) {
                    $data['brand'] = $brand;
                }

                $prices = $variant->getChannelPricings();
                $basePrice = null;
                foreach ($prices as $price) {
                    /** @var ChannelPricingInterface $price */
                    $basePrice = $price->getPrice();
                }

                if ($basePrice) {
                    $data['price'] = $basePrice;
                }

                $this->writeProduct($xmlWriter, $data);
            }
        }

        $xmlWriter->endElement();
        $xmlWriter->endDocument();
        $xmlWriter->flush();

        // create the directory structure
        $dir = $feedDir . '/' . $channel->getCode() . '/' . $locale->getCode();
        $this->filesystem->mkdir($dir);

        $this->filesystem->rename($tmpFile->getPathname(), $dir . '/' . $feed->getSlug() . '.xml', true);
    }

    private function writeProduct(\XMLWriter $xmlWriter, array $data): void
    {
        $xmlWriter->startElement('product');

        foreach ($data as $key => $val) {
            if (is_bool($val)) {
                $val = $this->boolToString($val);
            }

            $xmlWriter->writeElement($key, (string) $val);
        }

        $xmlWriter->endElement();
    }

    private function boolToString(bool $val): string
    {
        return $val ? 'true' : 'false';
    }

    private function getTmpFile(): \SplFileInfo
    {
        do {
            $filename = sys_get_temp_dir() . '/' . uniqid('feed-', true) . '.xml';
        } while ($this->filesystem->exists($filename));

        $this->filesystem->touch($filename);

        return new \SplFileInfo($filename);
    }

    /**
     * @return ProductInterface[]
     */
    private function getProducts()
    {
        return $this->productRepository->findAll();
    }
}
