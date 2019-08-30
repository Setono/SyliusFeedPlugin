<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Workflow;

final class FeedGraph
{
    public const GRAPH = 'setono_sylius_feed.feed';

    public const STATE_UNPROCESSED = 'unprocessed';

    public const STATE_PROCESSING = 'processing';

    public const STATE_READY = 'ready';

    public const STATE_ERROR = 'error';

    public const TRANSITION_PROCESS = 'process';

    public const TRANSITION_PROCESSED = 'processed';

    public const TRANSITION_ERRORED = 'errored';

    public static function getStates(): array
    {
        return [self::STATE_UNPROCESSED, self::STATE_PROCESSING, self::STATE_READY, self::STATE_ERROR];
    }
}
