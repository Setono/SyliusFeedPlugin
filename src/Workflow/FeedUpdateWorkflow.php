<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Workflow;

use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;
use Symfony\Component\Workflow\Transition;

final class FeedUpdateWorkflow
{
    private const PROPERTY_NAME = 'state';

    final public const NAME = 'setono_sylius_feed__feed_update';

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
            FeedUpdateInterface::STATE_PENDING,
            FeedUpdateInterface::STATE_PROCESSING,
            FeedUpdateInterface::STATE_COMPLETED,
            FeedUpdateInterface::STATE_FAILED,
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
                'supports' => FeedUpdateInterface::class,
                'initial_marking' => FeedUpdateInterface::STATE_PENDING,
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
                FeedUpdateInterface::STATE_PENDING,
                FeedUpdateInterface::STATE_PROCESSING,
            ),
            new Transition(
                self::TRANSITION_COMPLETE,
                FeedUpdateInterface::STATE_PROCESSING,
                FeedUpdateInterface::STATE_COMPLETED,
            ),
            new Transition(
                self::TRANSITION_FAIL,
                [FeedUpdateInterface::STATE_PENDING, FeedUpdateInterface::STATE_PROCESSING],
                FeedUpdateInterface::STATE_FAILED,
            ),
        ];
    }
}
