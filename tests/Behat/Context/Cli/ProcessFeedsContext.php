<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusFeedPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Safe\Exceptions\PcreException;
use Safe\Exceptions\StringsException;
use function Safe\preg_replace;
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

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var FeedProcessorInterface */
    private $processor;

    /** @var FeedPathGeneratorInterface */
    private $feedPathGenerator;

    /** @var RepositoryInterface */
    private $feedRepository;

    public function __construct(
        KernelInterface $kernel,
        FilesystemInterface $filesystem,
        FeedProcessorInterface $processor,
        FeedPathGeneratorInterface $feedPathGenerator,
        RepositoryInterface $feedRepository
    ) {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
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

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $path = $this->feedPathGenerator->resolve($feed, $channel->getCode(), $locale->getCode());

                Assert::true($this->filesystem->has($path));

                $expectedContent = $this->getExpectedContent($channel->getCode());
                $actualContent = $this->removeWhitespace($this->filesystem->read($path));

                Assert::same($actualContent, $expectedContent);
            }
        }
    }

    /**
     * @throws PcreException
     * @throws StringsException
     */
    private function getExpectedContent(string $channelCode): string
    {
        switch ($channelCode) {
            case 'denmark':
                $expectedContent = <<<CONTENT
<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>example.dk</title>
<link>https://example.dk</link>
<description></description>
<item>
<g:id>WARM_BEER</g:id><title>Warm beer</title><g:description>A good warm beer</g:description><link>http://localhost/en_US/products/warm-beer</link><g:availability>out of stock</g:availability><g:price>0 USD</g:price><g:condition>new</g:condition><g:item_group_id>WARM_BEER</g:item_group_id></item>
<item>
<g:id>WARM_BEER</g:id><title>Warm beer</title><g:description>A good warm beer</g:description><link>http://localhost/en_US/products/warm-beer</link><g:availability>in stock</g:availability><g:price>0 USD</g:price><g:condition>new</g:condition><g:item_group_id>WARM_BEER</g:item_group_id></item>
</channel>
</rss>
CONTENT;

                break;
            case 'united_states':
                $expectedContent = <<<CONTENT
<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>example.com</title>
<link>https://example.com</link>
<description></description>
<item>
<g:id>COLD_BEER</g:id><title>Cold beer</title><g:description>An ice cold beer</g:description><link>http://localhost/en_US/products/cold-beer</link><g:availability>out of stock</g:availability><g:price>0 USD</g:price><g:condition>new</g:condition><g:item_group_id>COLD_BEER</g:item_group_id></item>
<item>
<g:id>COLD_BEER</g:id><title>Cold beer</title><g:description>An ice cold beer</g:description><link>http://localhost/en_US/products/cold-beer</link><g:availability>in stock</g:availability><g:price>0 USD</g:price><g:condition>new</g:condition><g:item_group_id>COLD_BEER</g:item_group_id></item>
</channel>
</rss>
CONTENT;

                break;
            default:
                throw new InvalidArgumentException(\Safe\sprintf('No expected content for channel with code %s', $channelCode));
        }

        return $this->removeWhitespace($expectedContent);
    }

    /**
     * @throws PcreException
     */
    private function removeWhitespace(string $str): string
    {
        return preg_replace('/\s/', '', $str);
    }
}
