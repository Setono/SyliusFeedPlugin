<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Context;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class MessageContextFactory implements MessageContextFactoryInterface
{
    public function __construct(
        private readonly RepositoryInterface $channelRepository,
    ) {
    }

    public function create(?string $channelCode, ?string $locale, ?string $currencyCode): FeedContext
    {
        $channel = null;
        if (null !== $channelCode) {
            $candidate = $this->channelRepository->findOneBy(['code' => $channelCode]);
            $channel = $candidate instanceof ChannelInterface ? $candidate : null;
        }

        return new FeedContext($channel, $locale, $currencyCode);
    }
}
