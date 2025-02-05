<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

final class StartFeedUpdate
{
    public function __construct(
        /**
         * A list of feed ids to update. If empty, all feeds will be updated
         *
         * @var list<int> $feeds
         */
        public readonly array $feeds = [],
    ) {
    }
}
