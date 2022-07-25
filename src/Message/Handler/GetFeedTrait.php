<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

trait GetFeedTrait
{
    private FeedRepositoryInterface $feedRepository;

    private function getFeed(int $id): FeedInterface
    {
        /** @var FeedInterface|null $obj */
        $obj = $this->feedRepository->find($id);

        if (null === $obj) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed with id %s does not exist', $id));
        }

        return $obj;
    }
}
