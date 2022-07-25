<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

trait GetChannelTrait
{
    private ChannelRepositoryInterface $channelRepository;

    private function getChannel(int $id): ChannelInterface
    {
        /** @var ChannelInterface|null $obj */
        $obj = $this->channelRepository->find($id);

        if (null === $obj) {
            throw new UnrecoverableMessageHandlingException(sprintf('Channel with id %s does not exist', $id));
        }

        return $obj;
    }
}
