<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Proves the feed state machine prepended by the DI extension is registered with the expected
 * places and transitions.
 */
final class WorkflowConfigurationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_registers_the_feed_state_machine(): void
    {
        $stateMachine = self::getContainer()->get('state_machine.' . FeedGraph::GRAPH);

        self::assertInstanceOf(WorkflowInterface::class, $stateMachine);

        $definition = $stateMachine->getDefinition();

        self::assertEqualsCanonicalizing(FeedGraph::getStates(), array_keys($definition->getPlaces()));

        $transitionNames = array_map(
            static fn (Transition $transition): string => $transition->getName(),
            $definition->getTransitions(),
        );

        // A state_machine expands a transition with multiple `from` places (here `reset`) into
        // one Transition object per source place, so compare the distinct transition names.
        self::assertEqualsCanonicalizing(
            [
                FeedGraph::TRANSITION_PROCESS,
                FeedGraph::TRANSITION_COMPLETE,
                FeedGraph::TRANSITION_FAIL,
                FeedGraph::TRANSITION_RESET,
            ],
            array_values(array_unique($transitionNames)),
        );
    }
}
