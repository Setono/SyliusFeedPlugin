<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Workflow;

use Setono\SyliusFeedPlugin\Model\FeedUpdateBatchInterface;
use Symfony\Component\Workflow\Transition;

// todo create an abstract workflow to share between this and FeedUpdateWorkflow
final class FeedUpdateBatchWorkflow
{
    private const PROPERTY_NAME = 'state';

    final public const NAME = 'setono_sylius_feed__feed_update_batch';

    final public const TRANSITION_PROCESS = 'process';

    final public const TRANSITION_COMPLETE = 'complete';

    final public const TRANSITION_FAIL = 'fail';

    private function __construct()
    {
    }

    /**
     * @return non-empty-list<string>
     */
    public static function getStates(): array
    {
        return [
            FeedUpdateBatchInterface::STATE_PENDING,
            FeedUpdateBatchInterface::STATE_PROCESSING,
            FeedUpdateBatchInterface::STATE_COMPLETED,
            FeedUpdateBatchInterface::STATE_FAILED,
        ];
    }

    public static function getConfig(): array
    {
        $transitions = [];
        foreach (self::getTransitions() as $transition) {
            $transitions[$transition->getName()] = [
                'from' => $transition->getFroms(),
                'to' => $transition->getTos(),
            ];
        }

        return [
            self::NAME => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'method',
                    'property' => self::PROPERTY_NAME,
                ],
                'supports' => FeedUpdateBatchInterface::class,
                'initial_marking' => FeedUpdateBatchInterface::STATE_PENDING,
                'places' => self::getStates(),
                'transitions' => $transitions,
            ],
        ];
    }

    /**
     * @return non-empty-list<Transition>
     */
    public static function getTransitions(): array
    {
        return [
            new Transition(
                self::TRANSITION_PROCESS,
                FeedUpdateBatchInterface::STATE_PENDING,
                FeedUpdateBatchInterface::STATE_PROCESSING,
            ),
            new Transition(
                self::TRANSITION_COMPLETE,
                FeedUpdateBatchInterface::STATE_PROCESSING,
                FeedUpdateBatchInterface::STATE_COMPLETED,
            ),
            new Transition(
                self::TRANSITION_FAIL,
                [FeedUpdateBatchInterface::STATE_PENDING, FeedUpdateBatchInterface::STATE_PROCESSING],
                FeedUpdateBatchInterface::STATE_FAILED,
            ),
        ];
    }
}
