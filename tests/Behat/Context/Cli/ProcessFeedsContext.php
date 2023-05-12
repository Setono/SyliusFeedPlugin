<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusFeedPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Command\ProcessFeedsCommand;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Processor\FeedProcessorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress UndefinedDocblockClass
 * @psalm-suppress UndefinedClass
 */
final class ProcessFeedsContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var Application */
    private $application;

    /** @var CommandTester */
    private $tester;

    /** @var ProcessFeedsCommand */
    private $command;

    /** @var FilesystemInterface|FilesystemOperator */
    private $filesystem;

    /** @var FeedProcessorInterface */
    private $processor;

    /** @var FeedPathGeneratorInterface */
    private $feedPathGenerator;

    /** @var RepositoryInterface */
    private $feedRepository;

    /**
     * @psalm-suppress UndefinedDocblockClass
     *
     * @param FilesystemInterface|FilesystemOperator $filesystem
     */
    public function __construct(
        KernelInterface $kernel,
        $filesystem,
        FeedProcessorInterface $processor,
        FeedPathGeneratorInterface $feedPathGenerator,
        RepositoryInterface $feedRepository
    ) {
        $this->kernel = $kernel;
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            $this->filesystem = $filesystem;
        } elseif ($filesystem instanceof FilesystemOperator) {
            $this->filesystem = $filesystem;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class
            ));
        }
        $this->processor = $processor;
        $this->feedPathGenerator = $feedPathGenerator;
        $this->feedRepository = $feedRepository;
    }

    /**
     * @When I run the process command
     */
    public function iRunProcessCommand(): void
    {
        $this->application = new Application($this->kernel);
        $this->application->add(new ProcessFeedsCommand($this->processor));

        $this->command = $this->application->find('setono:sylius-feed:process');
        $this->tester = new CommandTester($this->command);

        $this->tester->execute(['command' => 'setono:sylius-feed:process']);
    }

    /**
     * @Then the command should run successfully
     */
    public function theCommandShouldRunSuccessfully(): void
    {
        Assert::same(0, $this->tester->getStatusCode());
    }

    /**
     * @Then two files should exist with the right content
     */
    public function aFileShouldExistWithTheRightContent(): void
    {
        /** @var FeedInterface[] $feeds */
        $feeds = $this->feedRepository->findAll();
        Assert::count($feeds, 1);

        $feed = $feeds[0];
        /** @var FilesystemInterface|FilesystemOperator $filesystem */
        $filesystem = $this->filesystem;

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $path = $this->feedPathGenerator->generate($feed, $channel->getCode(), $locale->getCode());

                if ($filesystem instanceof FilesystemOperator) {
                    Assert::true($filesystem->fileExists((string) $path));
                } else {
                    Assert::true($filesystem->has($path));
                }

                $expectedContent = $this->getExpectedContent($channel->getCode());
                if ($filesystem instanceof FilesystemOperator) {
                    $actualContent = $this->removeWhitespace($filesystem->read((string) $path));
                } else {
                    $actualContent = $this->removeWhitespace($filesystem->read($path));
                }
                $actualContent = $this->normalizeImageLink($actualContent);

                Assert::same($actualContent, $expectedContent);
            }
        }
    }

    private function getExpectedContent(string $channelCode): string
    {
        switch ($channelCode) {
            case 'denmark':
                $expectedContent = <<<CONTENT
<?xmlversion="1.0"?>
<rssxmlns:g="http://base.google.com/ns/1.0"version="2.0"><channel>
<title>example.dk</title>
<link>https://example.dk</link>
<description></description>
<item>
    <g:id>WARM_BEER</g:id>
    <title>Warmbeer</title>
    <g:description>Agoodwarmbeer</g:description>
    <link>https://example.dk/en_US/products/warm-beer</link>
    <g:image_link>https://example.dk/media/cache/resolve/sylius_shop_product_large_thumbnail/%image_path%</g:image_link>
    <g:availability>in_stock</g:availability>
    <g:price>0USD</g:price>
    <g:condition>new</g:condition>
    <g:item_group_id>WARM_BEER</g:item_group_id>
</item>
</channel></rss>
CONTENT;

                break;
            case 'united_states':
                $expectedContent = <<<CONTENT
<?xmlversion="1.0"?>
<rssxmlns:g="http://base.google.com/ns/1.0"version="2.0"><channel>
<title>example.com</title>
<link>https://example.com</link>
<description></description>
<item>
    <g:id>COLD_BEER</g:id>
    <title>Coldbeer</title>
    <g:description>Anicecoldbeer</g:description>
    <link>https://example.com/en_US/products/cold-beer</link>
    <g:image_link>https://example.com/media/cache/resolve/sylius_shop_product_large_thumbnail/%image_path%</g:image_link>
    <g:availability>in_stock</g:availability>
    <g:price>0USD</g:price>
    <g:condition>new</g:condition>
    <g:item_group_id>COLD_BEER</g:item_group_id>
</item>
</channel></rss>
CONTENT;

                break;
            default:
                throw new InvalidArgumentException(sprintf('No expected content for channel with code %s', $channelCode));
        }

        return $this->removeWhitespace($expectedContent);
    }

    private function removeWhitespace(string $str): string
    {
        return preg_replace('/\s/', '', $str);
    }

    private function normalizeImageLink(string $actualContent): ?string
    {
        return \preg_replace(
            '/sylius_shop_product_large_thumbnail\/.*?\.jpe?g/',
            'sylius_shop_product_large_thumbnail/%image_path%',
            $actualContent
        );
    }
}
