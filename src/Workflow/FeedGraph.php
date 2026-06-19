<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Workflow;

/**
 * The feed generation state machine (§6.4).
 *
 *   ready ──process──▶ processing ──complete──▶ completed
 *                                  └──fail─────▶ failed
 *   completed|failed ──reset──▶ ready
 */
final class FeedGraph
{
    public const GRAPH = 'setono_sylius_feed_feed';

    public const STATE_READY = 'ready';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public const TRANSITION_PROCESS = 'process';

    public const TRANSITION_COMPLETE = 'complete';

    public const TRANSITION_FAIL = 'fail';

    public const TRANSITION_RESET = 'reset';

    /**
     * @codeCoverageIgnore the class is a static holder; the private constructor only prevents instantiation
     */
    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function getStates(): array
    {
        return [self::STATE_READY, self::STATE_PROCESSING, self::STATE_COMPLETED, self::STATE_FAILED];
    }
}
