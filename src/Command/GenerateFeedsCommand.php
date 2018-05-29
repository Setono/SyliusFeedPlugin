<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;
use Loevgaard\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * TODO
 * * Use XML lib for writing xml file, i.e. http://sabre.io/xml/ or https://github.com/servo-php/fluidxml
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

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository,
        ProductRepositoryInterface $productRepository,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        RouterInterface $router
    ) {
        parent::__construct();

        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
        $this->productRepository = $productRepository;
        $this->productManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->router = $router;
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

    private function generateFeed(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale) : void
    {
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
        $xmlWriter->startDocument();
        $xmlWriter->startElement('products');

        foreach ($products as $product) {
            $link = $this->router->generate('sylius_shop_product_show', ['_locale' => $locale->getCode(), 'slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

            // outer product tag begin
            $xmlWriter->startElement('product');

            // add data tags
            $xmlWriter->writeElement('id', (string)$product->getId());
            $xmlWriter->writeElement('code', $product->getCode());
            $xmlWriter->writeElement('link', $link);
            $xmlWriter->writeElement('name', $product->getName());
            $xmlWriter->writeElement('short_description', $product->getShortDescription());
            $xmlWriter->writeElement('description', $product->getDescription());
            $xmlWriter->writeElement('main_taxon', $product->getMainTaxon() ? $product->getMainTaxon()->getName() : '');
            $xmlWriter->writeElement('created_at', $product->getCreatedAt()->format(DATE_ATOM));
            $xmlWriter->writeElement('updated_at', $product->getUpdatedAt()->format(DATE_ATOM));

            // outer product tag end
            $xmlWriter->endElement();

            echo (memory_get_usage(true) / 1024 / 1024)." MB\n";

            // todo flush when we've reached a certain threshold of PHP's max memory limit
            //$xmlWriter->flush();
        }

        $xmlWriter->endElement();
        $xmlWriter->endDocument();
        $xmlWriter->flush();

        // create the directory structure
        $dir = $feedDir.'/'.$channel->getCode().'/'.$locale->getCode();
        $this->filesystem->mkdir($dir);

        $this->filesystem->rename($tmpFile->getPathname(), $dir.'/'.$feed->getSlug().'.xml', true);
    }

    private function getTmpFile() : \SplFileInfo
    {
        do {
            $filename = sys_get_temp_dir().'/'.uniqid('feed-', true).'.xml';
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
