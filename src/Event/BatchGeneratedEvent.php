<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class BatchGeneratedEvent extends Event
{
    /** @var FeedInterface */
    private $feed;

    public function __construct(FeedInterface $feed)
    {
        $this->feed = $feed;
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }
}
