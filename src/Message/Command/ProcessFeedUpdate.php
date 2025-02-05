<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;

/**
 * @internal
 */
final class ProcessFeedUpdate implements AsyncCommandInterface
{
    public readonly int $feedUpdate;

    public function __construct(int|FeedUpdateInterface $feedUpdate)
    {
        if ($feedUpdate instanceof FeedUpdateInterface) {
            $feedUpdate = (int) $feedUpdate->getId();
        }

        $this->feedUpdate = $feedUpdate;
    }
}
