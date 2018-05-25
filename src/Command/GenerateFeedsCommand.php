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

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository,
        ProductRepositoryInterface $productRepository,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem
    ) {
        parent::__construct();

        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
        $this->productRepository = $productRepository;
        $this->productManager = $entityManager;
        $this->filesystem = $filesystem;
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
        $feedDir = $this->getContainer()->getParameter('loevgaard_sylius_feed.dir');

        $tmpFile = $this->getTmpFile();

        $tmpFile->fwrite(<<<START
<?xml version="1.0" encoding="UTF-8"?>
<products>
START
);

        $products = $this->getProducts();

        foreach ($products as $product) {
            $tmpFile->fwrite('<product>');
            $tmpFile->fwrite('<id>'.$product->getId().'</id>');
            $tmpFile->fwrite('<code>'.$product->getCode().'</code>');
            $tmpFile->fwrite('<name>'.$product->getName().'</name>');
            $tmpFile->fwrite('<short_description>'.$product->getShortDescription().'</short_description>');
            $tmpFile->fwrite('<description>'.$product->getDescription().'</description>');
            $tmpFile->fwrite('<created_at>'.$product->getCreatedAt()->format(DATE_ATOM).'</created_at>');
            $tmpFile->fwrite('<updated_at>'.$product->getUpdatedAt()->format(DATE_ATOM).'</updated_at>');
            $tmpFile->fwrite('<main_taxon>'.($product->getMainTaxon() ? $product->getMainTaxon()->getName() : '').'</main_taxon>');
            $tmpFile->fwrite('</product>');
        }

        $tmpFile->fwrite(<<<END
</products>
END
        );
        $tmpFile->fflush();

        // create the directory structure
        $dir = $feedDir.'/'.$channel->getCode().'/'.$locale->getCode();
        $this->filesystem->mkdir($dir);

        $this->filesystem->rename($tmpFile->getPathname(), $dir.'/'.$feed->getSlug().'.xml', true);
    }

    private function getTmpFile() : \SplFileObject
    {
        do {
            $filename = sys_get_temp_dir().'/'.uniqid('feed-', true).'.xml';
        } while ($this->filesystem->exists($filename));

        return new \SplFileObject($filename, 'w+');
    }

    /**
     * @return ProductInterface[]
     */
    private function getProducts()
    {
        return $this->productRepository->findAll();
    }
}
