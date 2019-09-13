<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusFeedPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class FeedContext implements Context
{
    /** @var FactoryInterface */
    private $feedFactory;

    /** @var RepositoryInterface */
    private $feedRepository;

    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    public function __construct(
        FactoryInterface $feedFactory,
        RepositoryInterface $feedRepository,
        ChannelRepositoryInterface $channelRepository
    ) {
        $this->feedFactory = $feedFactory;
        $this->feedRepository = $feedRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * @Given there is a feed with feed type :feedType
     */
    public function thereIsAFeedWithFeedType(string $feedType): void
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        /** @var FeedInterface $feed */
        $feed = $this->feedFactory->createNew();

        $feed->setName('Feed');
        $feed->setFeedType($feedType);
        foreach ($channels as $channel) {
            $feed->addChannel($channel);
        }

        $this->feedRepository->add($feed);
    }
}
