<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;

final class GenerateFeedHandler
{
    /**
     * @var FeedRepositoryInterface
     */
    private $feedRepository;
    /**
     * @var FeedGeneratorInterface
     */
    private $feedGenerator;

    public function __construct(FeedRepositoryInterface $feedRepository, FeedGeneratorInterface $feedGenerator)
    {
        $this->feedRepository = $feedRepository;
        $this->feedGenerator = $feedGenerator;
    }

    public function __invoke(GenerateFeed $message)
    {
        /** @var FeedInterface $feed */
        $feed = $this->feedRepository->find($message->getFeedId());

        if (null === $feed) {
            throw new \InvalidArgumentException('Feed does not exist'); // todo better exception
        }

        $this->feedGenerator->generate($feed);
    }
}
